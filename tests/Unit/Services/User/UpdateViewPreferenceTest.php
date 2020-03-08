<?php

namespace Tests\Unit\Services\User;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Services\User\UpdateViewPreference;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateViewPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_the_contact_view_preferences()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'preference' => 'last_first',
        ];

        $user = app(UpdateViewPreference::class)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'contacts_sort_order' => 'last_first',
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
        app(UpdateViewPreference::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_user_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create();

        $request = [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'preference' => 'last_first',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateViewPreference::class)->execute($request);
    }
}
