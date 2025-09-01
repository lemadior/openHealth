<?php

namespace App\Jobs;

use App\Models\LegalEntity;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncOwnerDetailsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        // protected string $token,
        // protected User $user,
        // protected LegalEntity $legalEntity
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Here will be placed code from the checkLoginedUser - part concerned OWNER : (will be done soon)
        echo "SyncOwnerDetailsJob COMPLETED" . PHP_EOL;
    }
}
