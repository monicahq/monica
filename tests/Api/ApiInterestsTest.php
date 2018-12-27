<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Interest;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiInterestsTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonInterest = [
        'id',
        'object',
        'name',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_interests_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest1 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest2 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/interests');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonInterest],
        ]);
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest2->id,
        ]);
    }

    public function test_interests_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest1 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest2 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/interests');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonInterest],
        ]);
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'interest',
            'id' => $interest2->id,
        ]);
    }

    public function test_interests_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/interests');

        $this->expectNotFound($response);
    }

    public function test_interests_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest1 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $interest2 = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/interests/'.$interest1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonInterest,
        ]);
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'interest',
            'id' => $interest2->id,
        ]);
    }

    public function test_interests_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/interests/0');

        $this->expectNotFound($response);
    }

    public function test_interests_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/interests', [
            'contact_id' => $contact->id,
            'name' => 'the name',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonInterest,
        ]);
        $interest_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest_id,
            'name' => 'the name',
        ]);

        $this->assertGreaterThan(0, $interest_id);
        $this->assertDatabaseHas('interests', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $interest_id,
            'name' => 'the name',
        ]);
    }

    public function test_interests_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/interests', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_interests_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/interests', [
            'contact_id' => $contact->id,
            'name' => 'test',
        ]);

        $this->expectNotFound($response);
    }

    public function test_interests_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/interests/'.$interest->id, [
            'contact_id' => $contact->id,
            'name' => 'the name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonInterest,
        ]);
        $interest_id = $response->json('data.id');
        $this->assertEquals($interest->id, $interest_id);
        $response->assertJsonFragment([
            'object' => 'interest',
            'id' => $interest_id,
            'name' => 'the name',
        ]);

        $this->assertGreaterThan(0, $interest_id);
        $this->assertDatabaseHas('interests', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $interest_id,
            'name' => 'the name',
        ]);
    }

    public function test_interests_update_error()
    {
        $user = $this->signin();
        $interest = factory(Interest::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/interests/'.$interest->id, [
            'contact_id' => $interest->contact_id,
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_interests_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $interest = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/interests/'.$interest->id, [
            'contact_id' => $contact->id,
            'name' => 'test',
        ]);

        $this->expectNotFound($response);
    }

    public function test_interests_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $interest = factory(Interest::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('interests', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $interest->id,
        ]);

        $response = $this->json('DELETE', '/api/interests/'.$interest->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('interests', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $interest->id,
        ]);
    }

    public function test_interests_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/interests/0');

        $this->expectNotFound($response);
    }
}
