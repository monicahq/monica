<?php

namespace App\Domains\Contact\Dav\Event;

use App\Models\SyncToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenDeleteEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public SyncToken $token
    ) {}
}
