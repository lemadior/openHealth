<?php

namespace App\Events;

use App\Livewire\LegalEntity\LegalEntity;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EHealthUserLogin
{
    use
        Dispatchable,
        //InteractsWithSockets,
        SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public LegalEntity $legalEntity,
        public bool $isFirstLogin,
        public bool $isOwner
    ) {}
}
