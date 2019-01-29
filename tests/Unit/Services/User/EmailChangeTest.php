<?php

namespace Tests\Unit\Services\User;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Services\User\EmailChange;
use App\Notifications\ConfirmEmail;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class EmailChangeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_update_user_email()
    {
        NotificationFacade::fake();
        config(['monica.signup_double_optin' => false]);

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $user = app(EmailChange::class)->execute($request);

        NotificationFacade::assertNotSentTo($user, ConfirmEmail::class);
        NotificationFacade::assertNothingSent();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account->id,
            'email' => 'newmail@ok.com',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'email' => 'email@email.com',
        ];

        $this->expectException(ValidationException::class);

        app(EmailChange::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_user_is_not_linked_to_account()
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

    public function test_it_update_user_email_and_send_confirmation()
    {
        NotificationFacade::fake();
        config(['monica.signup_double_optin' => true]);

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'email' => 'newmail@ok.com',
        ];

        $user = app(EmailChange::class)->execute($request);

        NotificationFacade::assertSentTo($user, ConfirmEmail::class);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account->id,
            'email' => 'newmail@ok.com',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }
}
