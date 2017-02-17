<?php

namespace App\Events\SignificantOther;

use App\SignificantOther;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SignificantOtherDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $significantOther;

    /**
     * Create a new event instance.
     *
     * @param  SignificantOther $contact
     * @return void
     */
    public function __construct(SignificantOther $significantOther)
    {
        $this->significantOther = $significantOther;
    }
}
