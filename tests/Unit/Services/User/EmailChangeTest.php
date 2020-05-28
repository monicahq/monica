<?php

namespace Tests\Unit\Services\User;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Services\User\EmailChange;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class EmailChangeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_user_email()
    {
        NotificationFacade::fake();
        config(['monica.signup_double_optin' => false]);

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $user = app(EmailChange::class)->execute($request);

        NotificationFacade::assertNotSentTo($user, VerifyEmail::class);
        NotificationFacade::assertNothingSent();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'email' => 'newmail@ok.com',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'email' => 'email@email.com',
        ];

        $this->expectException(ValidationException::class);

        app(EmailChange::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_user_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create();

        $request = [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $this->expectException(ModelNotFoundException::class);

        app(EmailChange::class)->execute($request);
    }

    /** @test */
    public function it_updates_user_email_and_send_confirmation()
    {
        NotificationFacade::fake();
        config(['monica.signup_double_optin' => true]);

        // Creating a fake account
        factory(Account::class)->create();

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $user = app(EmailChange::class)->execute($request);

        NotificationFacade::assertSentTo($user, VerifyEmail::class);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'email' => 'newmail@ok.com',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }

    /** @test */
    public function it_sends_confirmation_email()
    {
        NotificationFacade::fake();
        config(['monica.signup_double_optin' => true]);

        // Creating a fake account
        factory(Account::class)->create();

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $user = app(EmailChange::class)->execute($request);

        NotificationFacade::assertSentTo($user, VerifyEmail::class);

        $notifications = NotificationFacade::sent($user, VerifyEmail::class);
        $message = $notifications[0]->toMail($user);

        $this->assertStringContainsString('To validate your email click on the button below', implode('', $message->introLines));
    }
}
