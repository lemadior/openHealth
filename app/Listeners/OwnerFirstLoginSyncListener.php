<?php

namespace App\Listeners;

use App\Enums\JobStatus;
use App\Events\EHealthUserLogin;
use App\Jobs\SyncCompletedJob;
use App\Jobs\SyncDivisionsListJob;
use App\Jobs\SyncEmployeeListJob;
use App\Jobs\SyncHealthcareServicesListJob;

use App\Models\Division;
use App\Models\Employee\Employee;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Models\SyncJob;
use App\Models\User;
use App\Notifications\SyncNotification;
use App\Traits\ManagesSyncLock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class OwnerFirstLoginSyncListener implements ShouldQueue
{
    use InteractsWithQueue, ManagesSyncLock;

    /**
     * The number of times the listener may be attempted.
     */
    public int $tries = 5;

    protected const array SYNC_ENTITIES = [
        Division::class => SyncDivisionsListJob::class,
        HealthcareService::class => SyncHealthcareServicesListJob::class,
        Employee::class => SyncEmployeeListJob::class,
        User::class => SyncCompletedJob::class,
    ];

    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        // Check if user has active session, retry if not
        if (!$this->ensureUserHasActiveSession($event->user)) {
            return; // Session check failed, will retry automatically
        }

        $user = $event->user;
        $legalEntity = $event->legalEntity;

         // Prevent duplicate sync processes for the same user/legal entity
        if ($this->isSyncAlreadyInProgress($user, $legalEntity)) {
            echo "Sync already in progress for user {$user->id} and legal entity {$legalEntity->id}, skipping..." . PHP_EOL;
            \Log::info("OwnerFirstLoginSyncListener: Sync already in progress, skipping", [
                'user_id' => $user->id,
                'legal_entity_id' => $legalEntity->id
            ]);

            return;
        }

        echo "IN LISTENER OwnerFirstLoginSyncListener - Session confirmed" . PHP_EOL;
        \Log::info("IN LISTENER OwnerFirstLoginSyncListener");

        $isOwner = $user->employees()->where('employee_type', 'OWNER')->exists();

        \Log::debug('OwnerFirstLoginSyncListener:', ['user.uuid' => $event->user->uuid, 'legal_entity' => $event->legalEntity->id, 'isFirstLogin' => $event->isFirstLogin, 'isOwner' => $isOwner]);

        // Skip if user already has been authenticated
        if (! $event->isFirstLogin || ! $isOwner) {
            return;
        }

        $chainSteps= [];

        echo "token in Listener: " . $event->token . PHP_EOL;

        // Create a batch for main sync jobs
        $chainSteps = $this->getBatchesOfJobs($event->token, $user, $legalEntity);

        if (empty($chainSteps)) {
            echo "WARNING: No chain steps created! Sync will not start." . PHP_EOL;
            \Log::warning("OwnerFirstLoginSyncListener: No chain steps created", [
                'user_id' => $user->id,
                'legal_entity_id' => $legalEntity->id
            ]);
            return;
        }

        echo "Starting chain with " . count($chainSteps) . " steps" . PHP_EOL;

        Bus::chain($chainSteps)->dispatch();
    }

    protected function getBatchesOfJobs(string $token, User $user, LegalEntity $legalEntity): array
    {
        $chain = [];
        $isResumed = false;

        \Log::info("OwnerFirstLoginSyncListener: Starting batch creation", [
            'user_id' => $user->id,
            'legal_entity_id' => $legalEntity->id,
            'total_entities' => count(self::SYNC_ENTITIES)
        ]);

        foreach (self::SYNC_ENTITIES as $entityType => $jobClass) {
            // For User entity (SyncCompletedJob), allow creating new job if previous sync was finished (has finished_at)
            // For other entities, only allow if no record exists at all
            $canJobStartFromScratch = $entityType === User::class
                ? SyncJob::where('legal_entity_id', $legalEntity->id)
                    ->where('entity_type', $entityType)
                    ->whereNull('finished_at')
                    ->doesntExist()
                : SyncJob::where('legal_entity_id', $legalEntity->id)
                    ->where('entity_type', $entityType)
                    ->doesntExist();

            $jobToContinue = SyncJob::where('legal_entity_id', $legalEntity->id)
                ->where('entity_type', $entityType)
                ->where('status', JobStatus::PAUSED)
                ->first();

            // echo "Job to continue:[first] " . ($jobToContinue ? $jobToContinue->id : 'none') . PHP_EOL;

            // Try to find out is any PENDING jobs are presnet
            if (!$jobToContinue) {
                $jobToContinue = SyncJob::where('legal_entity_id', $legalEntity->id)
                ->where('entity_type', $entityType)
                ->where('status', JobStatus::PENDING)
                ->first();

            }

            // echo "Job to continue:[second] " . ($jobToContinue ? $jobToContinue->id : 'none') . PHP_EOL;

            // For User entity, check finished_at field; for others, check COMPLETED status
            $isJobFinished = $entityType === User::class
                ? SyncJob::where('legal_entity_id', $legalEntity->id)
                    ->where('entity_type', $entityType)
                    ->whereNotNull('finished_at')
                    ->exists()
                : SyncJob::where('legal_entity_id', $legalEntity->id)
                    ->where('entity_type', $entityType)
                    ->where('status', JobStatus::COMPLETED)
                    ->exists();

            // echo "Is job finished: " . ($isJobFinished ? 'yes' : 'no') . PHP_EOL;

            // For User entity, always allow creating new job even if previous sync was finished
            // For other entities, skip if already finished and no job to continue
            if ($isJobFinished && !$jobToContinue && $entityType !== User::class) {
                echo "Entity {$entityType}: Job already finished, skipping" . PHP_EOL;
                $isResumed = true;
                continue;
            }

            if ($canJobStartFromScratch) {
                // Create a new SyncJob record
                $syncJob = $legalEntity->syncJobs()->create([
                    'status' => JobStatus::PENDING, // Initial status,
                    'entity_type' => $entityType, // TODO: possible it will be removed or renamed to more obviosly (eg. 'sync'),
                    'user_id' => $user->id,
                    'page' => 1,
                ]);
            } else if ($jobToContinue) {
                $isResumed = true;
                // Continue from the existing SyncJob record
                $syncJob = $jobToContinue;
            } else {
                \Log::error("OwnerFirstLoginSyncListener: No valid job state for entity", [
                    'entity_type' => $entityType,
                    'legal_entity_id' => $legalEntity->id,
                    'can_start_from_scratch' => $canJobStartFromScratch,
                    'has_job_to_continue' => (bool)$jobToContinue,
                    'is_job_finished' => $isJobFinished
                ]);
                continue; // Skip this entity
            }

            $job = new $jobClass(token: $token, user: $user, syncJob: $syncJob, legalEntity: $legalEntity);

            $chain[] = $jobClass === SyncCompletedJob::class
                ?$job
                : Bus::batch([$job]);
        }

        \Log::info("OwnerFirstLoginSyncListener: Batch creation completed", [
            'user_id' => $user->id,
            'legal_entity_id' => $legalEntity->id,
            'total_chain_steps' => count($chain),
            'is_resumed' => $isResumed
        ]);

        $user->notify(new SyncNotification('legal_entity', $isResumed ? 'resumed' : 'started'));

        return $chain;
    }

    /**
     * Ensure user has active session, retry if not
     *
     * @param User $user
     *
     * @return bool
     */
    private function ensureUserHasActiveSession(User $user): bool
    {
        $hasActiveSession = DB::table('sessions')
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasActiveSession) {
            echo "IN LISTENER OwnerFirstLoginSyncListener - Session not ready, attempt {$this->attempts()}/{$this->tries}" . PHP_EOL;
            \Log::info("OwnerFirstLoginSyncListener: No active session, retrying", [
                'user_id' => $user->id,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            // Check if we've exceeded max attempts
            if ($this->attempts() >= $this->tries) {
                echo "Max attempts reached, giving up" . PHP_EOL;
                \Log::warning("OwnerFirstLoginSyncListener: Max attempts reached, giving up", [
                    'user_id' => $user->id,
                    'attempts' => $this->attempts()
                ]);
                return false; // Let it fail naturally
            }

            $this->release(3);
            return false;
        }

        return true;
    }

    /**
     * Check if sync is already in progress for this user/legal entity combination
     *
     * @param User $user
     * @param LegalEntity $legalEntity
     * @return bool
     */
    private function isSyncAlreadyInProgress(User $user, LegalEntity $legalEntity): bool
    {
        // Use Laravel's atomic lock mechanism - much simpler and more reliable
        $lockKey = "sync_in_progress:{$user->id}:{$legalEntity->id}";

        // Try to acquire a lock for 5 minutes (duration of sync process)
        $lock = cache()->lock($lockKey, 300);

        if ($lock->get()) {
            // We got the lock, which means no sync is in progress
            // Don't release it here - it will be released when sync completes or times out
            echo "Acquired sync lock for user {$user->id}, legal entity {$legalEntity->id}" . PHP_EOL;
            return false; // No sync in progress, we can proceed
        } else {
            // Could not get lock, sync is already in progress
            echo "Sync already in progress for user {$user->id}, legal entity {$legalEntity->id}" . PHP_EOL;
            return true;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(EHealthUserLogin $event, \Throwable $exception): void
    {
        \Log::error('OwnerFirstLoginSyncListener failed after all attempts', [
            'user_id' => $event->user->id,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        echo "OwnerFirstLoginSyncListener failed after {$this->attempts()} attempts for user {$event->user->id}" . PHP_EOL;

        $event->user->notify(new SyncNotification('legal_entity', action: 'failed'));

        // Ensure the sync lock is released on failure
        $this->releaseSyncLock($event->user, $event->legalEntity, 'listener failure');
    }
}
