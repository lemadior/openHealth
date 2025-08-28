<?php

namespace App\Listeners;

use App\Events\EHealthUserLogin;
use App\Jobs\SyncEmployeeDetailsJob;
use App\Jobs\SyncOwnerDetailsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncCurrentEmployeeListener
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

        // Skip if user already has been authenticated
        if (! $event->isFirstLogin || $user->uuid) {
            return;
        }

        // Just for concept it is not really works for now
        if ($event->isOwner) {
            SyncOwnerDetailsJob::dispatch($user, $event->legalEntity);
        } else {
            SyncEmployeeDetailsJob::dispatch($user, $event->legalEntity);
        }
    }
}
