<?php

namespace Tests\Api\Account;

use Tests\ApiTestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiPlaceControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonPlace = [
        'id',
        'object',
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
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_places()
    {
        $user = $this->signin();

        factory(Place::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/places');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonPlace],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        factory(Place::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/places?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 1,
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/places?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 2,
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_one_place()
    {
        $user = $this->signin();

        $place = factory(Place::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('get', '/api/places/'.$place->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonPlace,
        ]);
        $response->assertJsonFragment([
            'object' => 'place',
            'id' => $place->id,
        ]);
    }

    public function test_it_cant_get_a_call_with_unexistent_id()
    {
        $user = $this->signin();

        $response = $this->json('get', '/api/places/0');

        $this->expectNotFound($response);
    }

    public function test_it_create_a_place()
    {
        $user = $this->signin();

        $response = $this->json('post', '/api/places', [
            'city' => 'New York',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonPlace,
        ]);

        $placeId = $response->json('data.id');

        $response->assertJsonFragment([
            'object' => 'place',
            'id' => $placeId,
        ]);

        $this->assertDatabaseHas('places', [
            'account_id' => $user->account_id,
            'id' => $placeId,
            'city' => 'New York',
            'latitude' => null,
        ]);
    }

    public function test_it_updates_a_place()
    {
        $user = $this->signin();

        $place = factory(Place::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('put', '/api/places/'.$place->id, [
            'city' => 'New York',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonPlace,
        ]);

        $placeId = $response->json('data.id');

        $this->assertEquals($place->id, $placeId);

        $response->assertJsonFragment([
            'object' => 'place',
            'id' => $placeId,
        ]);

        $this->assertDatabaseHas('places', [
            'account_id' => $user->account_id,
            'id' => $placeId,
            'city' => 'New York',
            'latitude' => null,
        ]);
    }

    public function test_it_cant_update_a_place_if_account_is_not_linked_to_place()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $place = factory(Place::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/places/'.$place->id, [
            'city' => 'New York',
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_deletes_a_place()
    {
        $user = $this->signin();

        $place = factory(Place::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('delete', '/api/places/'.$place->id);

        $response->assertStatus(200);

        $this->assertdatabasemissing('places', [
            'account_id' => $user->account_id,
            'id' => $place->id,
        ]);
    }

    public function test_it_cant_delete_a_place_if_place_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('delete', '/api/places/0');

        $this->expectDataError($response, [
            'The selected place id is invalid.',
        ]);
    }
}
