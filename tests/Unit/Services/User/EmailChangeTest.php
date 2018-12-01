<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

namespace Tests\Unit\Services\User;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Services\User\EmailChange;
use App\Notifications\ConfirmEmail;
use App\Exceptions\MissingParameterException;
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

        $emailChangeService = new EmailChange;
        $user = $emailChangeService->execute($request);

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

        $this->expectException(MissingParameterException::class);

        $emailChangeService = new EmailChange;
        $user = $emailChangeService->execute($request);
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

        $emailChangeService = (new EmailChange)->execute($request);
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

        $emailChangeService = new EmailChange;
        $user = $emailChangeService->execute($request);

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
