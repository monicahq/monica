<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiCallControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCall = [
        'id',
        'object',
        'called_at',
        'content',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_calls()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/calls');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCall],
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_it_gets_the_calls_of_a_contact()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/calls');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCall],
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_calling_calls_get_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/calls');

        $this->expectNotFound($response);
    }

    public function test_it_gets_one_call()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/calls/'.$call1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_calling_one_call_gets_an_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/calls/0');

        $this->expectNotFound($response);
    }

    public function test_it_creates_a_call()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $callId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $callId,
        ]);

        $this->assertGreaterThan(0, $callId);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $callId,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);
    }

    public function test_create_calls_gets_an_error_if_fields_are_missing()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
        ]);

        $this->expectInvalidParameter($response, [
            'The called at field is required.',
        ]);
    }

    public function test_it_cant_create_a_call_if_account_is_wrong()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_updates_a_call()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $callId = $response->json('data.id');
        $this->assertEquals($call->id, $callId);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $callId,
        ]);

        $this->assertGreaterThan(0, $callId);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $callId,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);
    }

    public function test_updating_call_generates_an_error()
    {
        $user = $this->signin();
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'contact_id' => $call->contact_id,
        ]);

        $this->expectInvalidParameter($response, [
            'The called at field is required.',
        ]);
    }

    public function test_it_cant_update_a_call_if_account_is_not_linked_to_call()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_deletes_a_call()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call->id,
        ]);

        $response = $this->json('DELETE', '/api/calls/'.$call->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call->id,
        ]);
    }

    public function test_it_cant_delete_a_call_if_call_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/calls/0');

        $this->expectNotFound($response);
    }

    public function test_it_cant_delete_a_call_if_account_is_not_linked()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([]);
        $call = factory(Call::class)->create([
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('DELETE', '/api/calls/'.$call->id);

        $this->expectNotFound($response);
    }
}
