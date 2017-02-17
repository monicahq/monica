<?php

namespace App\Events\Gift;

use App\Gift;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GiftCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gift;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Gift $gift)
    {
        $this->gift = $gift;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
