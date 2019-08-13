<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Services\Contact\Contact\SetMeContact;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SetMeContactTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_set_me_as_a_a_contact()
    {
        $user = factory(User::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'contact_id' => $contact->id,
        ];

        $user = app(SetMeContact::class)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account->id,
            'me_contact_id' => $contact->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $user = factory(User::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'contact_id' => 0,
        ];

        $this->expectException(ValidationException::class);
        app(SetMeContact::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_not_found()
    {
        $user = factory(User::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'contact_id' => $contact->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(SetMeContact::class)->execute($request);
    }
}
