<?php

namespace App\Events;

use App\Models\User\SyncToken;
use Illuminate\Queue\SerializesModels;

class TokenDeleteEvent extends Event
{
    use SerializesModels;

    /**
     * The deleted token.
     *
     * @var SyncToken
     */
    public $token;

    /**
     * Create a new event instance.
     *
     * @param  SyncToken  $token
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
}
