<?php

namespace Tests\Unit\Controllers\Auth;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Notifications\StayInTouchEmail;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_send_password_reset_email()
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
