<?php

namespace Tests\Unit\Controllers\Auth;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_password_reset_email()
    {
        NotificationFacade::fake();

        $user = factory(User::class)->create();

        $this->post('/password/email', ['email' => $user->email]);

        NotificationFacade::assertSentTo($user, ResetPassword::class);

        $notifications = NotificationFacade::sent($user, ResetPassword::class);
        $message = $notifications[0]->toMail($user);

        $this->assertStringContainsString('You are receiving this email because we received a password reset request for your account.', implode('', $message->introLines));
    }
}
