<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiMeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_me_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/me/contact', ['contact_id' => $contact->id]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'account_id' => $user->account_id,
            'me_contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_throws_an_error_if_wrong_account_on_sets_me_contact()
    {
        $this->signin();
        $contact = factory(Contact::class)->create();

        $response = $this->json('POST', '/api/me/contact', ['contact_id' => $contact->id]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_throws_an_error_if_account_not_exists_on_sets_me_contact()
    {
        $this->signin();

        $response = $this->json('POST', '/api/me/contact', ['contact_id' => 0]);

        $this->expectDataError($response, [
            'The selected contact id is invalid.',
        ]);
    }

    /** @test */
    public function it_removes_me_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $user->me_contact_id = $contact->id;
        $user->save();

        $response = $this->json('DELETE', '/api/me/contact');

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'account_id' => $user->account_id,
            'me_contact_id' => null,
        ]);
    }
}
