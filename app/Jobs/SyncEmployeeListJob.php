<?php

namespace App\Jobs;

use App\Enums\JobStatus;
use App\Jobs\Middleware\EnsureUserHasActiveSession;
use App\Jobs\Middleware\PreventDuplicateJobs;
use App\Models\User;
use App\Models\SyncJob;
use App\Models\LegalEntity;
use App\Traits\ManagesSyncLock;
use Illuminate\Bus\Batchable;
use App\Notifications\SyncNotification;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncEmployeeListJob implements ShouldQueue
{
    use Queueable,
        Batchable,
        ManagesSyncLock;

    /** @var int Rate limit delay in seconds (50 requests per minute = 1 request every 1.2s, using 2s for safety) */
    private const int RATE_LIMIT_DELAY = 3;

    public int $tries = 3;
    public int $timeout = 60;

    /** @var string Entity type for duplicate job prevention */
    public string $entityType = \App\Models\Employee\Employee::class;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EnsureUserHasActiveSession,
            new PreventDuplicateJobs
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $token,
        public User $user,
        public LegalEntity $legalEntity,
        public ?SyncJob $syncJob = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->syncJob?->markAsProcessing();

        $this->user->notify(new SyncNotification('employee', 'completed'));
        // Here will be placed code from the checkLoginedUser - part concerned OWNER : (will be done soon)
        echo "SyncEmployeeJob COMPLETED" . PHP_EOL;

        $this->syncJob?->markAsCompleted();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        echo "SyncEmployeeListJob FAILED: " . $exception->getMessage() . PHP_EOL;

        if ($this->syncJob && $this->syncJob->status !== JobStatus::PAUSED) {
            $this->syncJob?->markAsFailed();

            $this->user->notify(new SyncNotification('employee', 'failed'));
        }

        // Ensure the sync lock is released on failure as well
        $this->releaseSyncLock($this->user, $this->legalEntity, 'employee sync failure');
    }
}
