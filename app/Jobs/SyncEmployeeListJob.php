<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\SyncJob;
use App\Models\LegalEntity;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncEmployeeListJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected LegalEntity $legalEntity,
        protected ?SyncJob $syncJob = null,
        protected int $page = 1
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Here will be placed code from the checkLoginedUser - part concerned Employee : (will be done soon)
    }
}
