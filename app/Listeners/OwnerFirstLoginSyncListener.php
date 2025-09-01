<?php

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\JobStatus;
use App\Events\EHealthUserLogin;
use App\Jobs\ChainCompletedJob;
use App\Jobs\SyncCompletedJob;
use App\Jobs\SyncDivisionDetailsJob;
use App\Jobs\SyncDivisionsListJob;
use App\Jobs\SyncEmployeeListJob;
use App\Jobs\SyncHealthcareServicesListJob;
use App\Jobs\SyncOwnerDetailsJob;

use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Models\SyncJob;
use App\Models\User;
use App\Notifications\CustomMessage;
use App\Notifications\SyncNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Batch;
use Throwable;

class OwnerFirstLoginSyncListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the listener may be attempted.
     */
    public int $tries = 5;

    protected const array SYNC_ENTITIES = [
        Division::class => SyncDivisionsListJob::class,
        HealthcareService::class => SyncHealthcareServicesListJob::class,
        // Employee::class => SyncEmployeeListJob::class,
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

        echo "IN LISTENER OwnerFirstLoginSyncListener - Session confirmed" . PHP_EOL;
        \Log::info("IN LISTENER OwnerFirstLoginSyncListener");

        $user = $event->user;
        $legalEntity = $event->legalEntity;

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

        $chainSteps[] = new SyncOwnerDetailsJob();
        $chainSteps[] = new SyncCompletedJob($user);

        Bus::chain($chainSteps)->dispatch();

        echo "Started chain with " . count($chainSteps) . " steps" . PHP_EOL;
    }

    protected function getBatchesOfJobs(string $token, User $user, LegalEntity $legalEntity): array
    {
        $chain = [];
        $isResumed = false;

        foreach (self::SYNC_ENTITIES as $entityType => $jobClass) {
            $canJobStartFromScratch = SyncJob::where('legal_entity_id', $legalEntity->id)
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

            $isJobFinished = SyncJob::where('legal_entity_id', $legalEntity->id)
                ->where('entity_type', $entityType)
                ->where('status', JobStatus::COMPLETED)
                ->exists();

            // echo "Is job finished: " . ($isJobFinished ? 'yes' : 'no') . PHP_EOL;

            if ($isJobFinished && !$jobToContinue) {
                $isResumed = true;

                continue;
            }

            if ($canJobStartFromScratch) {
                // echo "Starting new job for entity: " . $entityType . PHP_EOL;
                // Create a new SyncJob record
                $syncJob = $legalEntity->syncJobs()->create([
                    'status' => JobStatus::PENDING, // Initial status,
                    'entity_type' => $entityType, // TODO: possible it will be removed or renamed to more obviosly (eg. 'sync'),
                    'user_id' => $user->id,
                    'page' => 1,
                ]);
            } else if ($jobToContinue) {
                // echo "Continuing job ID: " . $jobToContinue->id . PHP_EOL;

                $isResumed = true;
                // Continue from the existing SyncJob record
                $syncJob = $jobToContinue;
            }

            $chain[] = Bus::batch([new $jobClass($token, $user, $legalEntity, $syncJob)]);
        }

        if ($isResumed) {
            $user->notify(new SyncNotification('legal_entity', 'resumed'));
        } else {
            $user->notify(new SyncNotification('legal_entity', 'started'));
        }

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
    }
}
