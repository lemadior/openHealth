<?php

namespace App\Jobs;

use App\Models\LegalEntity;
use App\Models\User;
use App\Notifications\SyncNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCompletedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        // protected string $token,
        protected User $user,
        // protected LegalEntity $legalEntity
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new SyncNotification('legal_entity', 'completed'));
        // Here will be placed code from the checkLoginedUser - part concerned OWNER : (will be done soon)
        echo "SyncCompletedJob COMPLETED" . PHP_EOL;
    }
}
