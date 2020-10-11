<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\DeleteMeContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteMeContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_set_me_as_a_a_contact()
    {
        $user = factory(User::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $user->me_contact_id = $contact->id;
        $user->save();

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
        ];

        $user = app(DeleteMeContact::class)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account->id,
            'me_contact_id' => null,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'user_id' => 0,
        ];

        $this->expectException(ValidationException::class);
        app(DeleteMeContact::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_not_found()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create();

        $request = [
            'account_id' => $account->id,
            'user_id' => $user->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DeleteMeContact::class)->execute($request);
    }
}
