<?php

namespace Tests\Api;

use Tests\ApiTestCase;

class ApiAdressesTest extends ApiTestCase
{
    public function test_address_get_contacts()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
            ]);

        $response = $this->json('GET', '/api/contacts');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
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
    }

    public function test_address_get_contactid()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
            ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
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
    }

    public function test_address_get_contactid_address()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
            ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id.'/addresses');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
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
    }

    public function test_address_get_addressid()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
            ]);

        $response = $this->json('GET', '/api/addresses/'.$address->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'object' => 'address',
            'id' => $address->id,
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
    }

    public function test_address_post()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);

        $response = $this->json('POST', '/api/addresses', [
            'account_id' => $user->account->id,
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
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
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

        $address_id = $response->json('data.id');
        $this->assertGreaterThan(0, $address_id);
    }

    public function test_address_put()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
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
            'street' => 'street',
            'postal_code' => '12345',
        ]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $address->id,
            'name' => 'address name up',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'US',
        ]);
    }

    public function test_address_delete()
    {
        $user = $this->signin();
        $contact = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $address = factory('App\Address')->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'name' => 'address name',
            'street' => 'street',
            'postal_code' => '12345',
            'country' => 'FR',
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
}
