<?php

namespace App\Events\Kid;

use App\Kid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KidDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $kid;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Kid $kid)
    {
        $this->kid = $kid;
    }
}
