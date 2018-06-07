<?php

namespace App\Listeners;

use App\Events\OrderShipped;

class LoginSucceed2fa
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrderShipped  $event
     * @return void
     */
    public function handle(OrderShipped $event)
    {
        // Access the order using $event->order...
        session([config('u2f.sessionU2fName') => true]);
    }
}