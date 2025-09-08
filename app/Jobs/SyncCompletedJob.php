<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\SyncJob;
use App\Enums\JobStatus;
use App\Models\LegalEntity;
use App\Traits\ManagesSyncLock;
use App\Notifications\SyncNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\Middleware\EnsureUserHasActiveSession;

class SyncCompletedJob implements ShouldQueue
{
    use Queueable,
        ManagesSyncLock;

    /** @var string Entity type for duplicate job prevention */
    public string $entityType = User::class;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EnsureUserHasActiveSession
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $token,
        public User $user,
        public ?SyncJob $syncJob = null,
        public ?LegalEntity $legalEntity = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->syncJob?->markAsProcessing();

        $this->user->notify(new SyncNotification('legal_entity', 'completed'));
        // Here will be placed code from the checkLoginedUser - part concerned OWNER : (will be done soon)
        echo "SyncCompletedJob COMPLETED" . PHP_EOL;

        // Clean up completed sync jobs (except SyncCompletedJob itself)
        if ($this->legalEntity) {
            $this->cleanupCompletedSyncJobs();
        }

        // Set completed status & finished_at for the SyncCompletedJob
        $this->syncJob?->markAsCompleted();

        $this->syncJob?->markAsFinished();

        // Release the sync lock when all sync is completed
        if ($this->legalEntity) {
            $this->releaseSyncLock($this->user, $this->legalEntity, 'sync completion');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        echo "SyncCompletedJob FAILED: " . $exception->getMessage() . PHP_EOL;

        if ($this->syncJob && $this->syncJob->status !== JobStatus::PAUSED) {
            $this->syncJob?->markAsFailed();

            $this->user->notify(new SyncNotification('legal_entity', 'failed'));
        }

        // Release the sync lock on failure as well
        if ($this->legalEntity) {
            $this->releaseSyncLock($this->user, $this->legalEntity, 'sync completion failure');
        }
    }

    /**
     * Clean up completed sync jobs except for the SyncCompletedJob itself
     */
    protected function cleanupCompletedSyncJobs(): void
    {
        $deletedCount = SyncJob::where('legal_entity_id', $this->legalEntity->id)
            ->where('user_id', $this->user->id)
            ->where('entity_type', '!=', User::class) // Keep SyncCompletedJob (User entity)
            ->whereIn('status', [JobStatus::COMPLETED])
            ->delete();

        echo "Cleaned up first login sync jobs" . PHP_EOL;
    }
}
