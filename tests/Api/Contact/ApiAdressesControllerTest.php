<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiAdressesControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonAddress = [
        'id',
        'object',
        'name',
        'street',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'country',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_addresses()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/addresses');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonAddress],
        ]);

        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
        ]);

        $response->assertJsonFragment([
            'total' => 1,
            'current_page' => 1,
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        factory(Address::class, 20)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/addresses?limit=1');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'total' => 20,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 20,
        ]);

        $response = $this->json('GET', '/api/addresses?limit=2');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'total' => 20,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 10,
        ]);
    }

    public function test_it_gets_addresses_for_a_specific_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id.'/addresses');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
            'name' => $address->name,
        ]);
    }

    public function test_calling_addresses_gets_an_error_if_contact_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/addresses');

        $this->expectNotFound($response);
    }

    public function test_it_gets_a_specific_address()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/addresses/'.$address->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonAddress,
        ]);

        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
            'name' => $address->name,
            'street' => $address->place->street,
        ]);
    }

    public function test_it_creates_an_address()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);

        $response = $this->json('POST', '/api/addresses', [
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
        ]);

        $response->assertJsonFragment([
            'object' => 'address',
            'name' => 'address name',
            'country' => [
                'object' => 'country',
                'id' => 'FR',
                'name' => 'France',
                'iso' => 'FR',
            ],
            'street' => 'street',
            'postal_code' => '12345',
        ]);

        $addressId = $response->json('data.id');
        $this->assertGreaterThan(0, $addressId);
    }

    public function test_create_addresses_gets_an_error_if_fields_are_missing()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);

        $response = $this->json('POST', '/api/addresses', [
        ]);

        $this->expectInvalidParameter($response, [
            'The contact id field is required.',
        ]);
    }

    public function test_create_addresses_gets_an_error_if_contact_is_not_linked_to_user()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/addresses', [
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_updates_an_address()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
        ]);

        $response = $this->json('PUT', '/api/addresses/'.$address->id, [
            'contact_id' => $contact->id,
            'name' => 'address name up',
            'country' => 'US',
        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
            'name' => 'address name up',
            'country' => [
                'object' => 'country',
                'id' => 'US',
                'name' => 'United States',
                'iso' => 'US',
            ],
            'postal_code' => $address->place->postal_code,
        ]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'place_id' => $address->place->id,
            'id' => $address->id,
            'name' => 'address name up',
        ]);
    }

    public function test_updating_address_generates_an_error()
    {
        $user = $this->signin();
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/addresses/'.$address->id, []);

        $this->expectInvalidParameter($response, [
            'The contact id field is required.',
        ]);
    }

    public function test_it_cant_update_an_address_if_account_is_not_linked_to_address()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/addresses/'.$address->id, [
            'contact_id' => $contact->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_deletes_an_address()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $address = factory(Address::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('DELETE', '/api/addresses/'.$address->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $address->id,
            'deleted' => true,
        ]);

        $this->assertDatabaseMissing('addresses', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $address->id,
        ]);
    }

    public function test_address_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/addresses/0');

        $this->expectInvalidParameter($response, [
            'The selected address id is invalid.',
        ]);
    }
}
