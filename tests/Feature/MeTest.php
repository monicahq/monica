<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MeTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_me()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/me/contact', [
            'contact_id' => $contact->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'true',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'me_contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_stores_error_wrong_parameter()
    {
        $this->signin();

        $response = $this->json('POST', '/me/contact', []);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'contact_id' => ['The contact id field is required.'],
            ],
        ]);
    }

    /** @test */
    public function it_stores_error_bad_account()
    {
        $this->signin();

        $contact = factory(Contact::class)->create();

        $response = $this->json('POST', '/me/contact', [
            'contact_id' => $contact->id,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Contact\\Contact] {$contact->id}",
        ]);
    }

    /** @test */
    public function it_deletes_me()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $user->me_contact_id = $contact->id;
        $user->save();

        $response = $this->json('DELETE', '/me/contact');

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'me_contact_id' => null,
        ]);
    }
}
