<?php

namespace Tests\Unit;

use App\Jobs\SendNewUserAlert;
use App\Mail\NewUserAlert;
use App\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendNewUserAlertTest extends TestCase
{
    use DatabaseTransactions;

    public function test_send_new_user_alert()
    {
        $receiver = factory(User::class)->create();

        // Setup the mocked mailler class from the laravel framework
        Mail::fake();

        // Call handling method to trigger action
        (new SendNewUserAlert($receiver))->handle();

        // Assert mail has been sent
        Mail::assertSent(NewUserAlert::class, function($mail) use ($receiver) {
            return $mail->hasTo(config('monica.email_new_user_notification'));
        });
    }
}
