<?php

namespace App\Listeners;

use App\Events\EHealthUserLogin;
use App\Jobs\SyncDivisionDetailsJob;
use App\Jobs\SyncDivisionsListJob;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StartDivisionSyncListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;
        $legalEntity = $event->legalEntity;

        // Skip if user already has been authenticated
        if (! $event->isFirstLogin || $user->uuid) {
            return;
        }

        SyncDivisionsListJob::dispatch($legalEntity);
    }
}
