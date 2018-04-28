<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureContact = [
        'id',
        'object',
        'hash_id',
        'first_name',
        'last_name',
        'gender',
        'is_partial',
        'is_dead',
        'last_called',
        'last_activity_together',
        'stay_in_touch_frequency',
        'stay_in_touch_trigger_date',
        'information' => [
            'relationships' => [
                'love' => [
                    'total',
                    'contacts',
                ],
                'family' => [
                    'total',
                    'contacts',
                ],
                'friend' => [
                    'total',
                    'contacts',
                ],
                'work' => [
                    'total',
                    'contacts',
                ],
            ],
            'dates' => [
                'birthdate' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
                'deceased_date' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
            ],
            'career',
            'avatar',
            'food_preferencies',
            'how_you_met',
        ],
        'addresses',
        'tags',
        'statistics',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonStructureContactShort = [
        'id',
        'object',
        'hash_id',
        'first_name',
        'last_name',
        'gender',
        'is_partial',
        'is_dead',
        'information' => [
            'dates' => [
                'birthdate' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
                'deceased_date' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
            ],
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_contacts()
    {
        $user = $this->signin();

        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );
    }

    public function test_it_contains_pagination_when_fetching_contacts()
    {
        $user = $this->signin();

        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/contacts?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_is_possible_to_search_contacts_with_query()
    {
        $user = $this->signin();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
            'first_name' => 'bob',
        ]);

        $response = $this->json('GET', '/api/contacts?query=ro');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => 'roger',
            'total' => 1,
            'query' => 'ro',
        ]);
    }

    public function test_it_is_possible_to_search_contacts_and_limit_query()
    {
        $user = $this->signin();

        $contact = factory('App\Contact', 2)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
            'first_name' => 'bob',
        ]);

        $response = $this->json('GET', '/api/contacts?query=ro&limit=1');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => 'roger',
            'total' => 2,
            'query' => 'ro',
            'per_page' => '1',
            'current_page' => 1,
        ]);
    }

    public function test_it_is_possible_to_search_contacts_and_limit_query_and_paginate()
    {
        $user = $this->signin();

        $contact = factory('App\Contact', 2)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory('App\Contact', 10)->create([
            'account_id' => $user->account_id,
            'first_name' => 'bob',
        ]);

        $response = $this->json('GET', '/api/contacts?query=ro&limit=1&page=2');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => 'roger',
            'total' => 2,
            'query' => 'ro',
            'per_page' => '1',
            'current_page' => 2,
        ]);
    }

    public function test_it_gets_a_contact()
    {
        $user = $this->signin();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => 'roger',
            'object' => 'contact',
        ]);
    }

    public function test_getting_a_contact_matches_a_specific_json_structure()
    {
        $user = $this->signin();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
    }

    public function test_getting_a_partial_contact_matches_a_specific_json_structure()
    {
        $user = $this->signin();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
            'is_partial' => true,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContactShort,
        ]);
    }

    public function test_getting_a_contact_with_the_parameter_with_matches_a_specific_json_structure()
    {
        $user = $this->signin();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        $response = $this->json('GET', '/api/contacts?with=contactfields');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureContact,
            ],
        ]);
    }
}
