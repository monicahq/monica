<?php

namespace Tests\Api\Contact;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
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
        'nickname',
        'gender',
        'is_starred',
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

    protected $jsonStructureContactWithContactFields = [
        'id',
        'object',
        'hash_id',
        'first_name',
        'last_name',
        'gender',
        'is_starred',
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
        'contactFields' => [],
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
        'nickname',
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

        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class, 2)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class, 2)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory(Contact::class, 10)->create([
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

        $contact = factory(Contact::class)->create([
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

        $contact = factory(Contact::class)->create([
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

        $contact = factory(Contact::class)->create([
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

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
        ]);

        $response = $this->json('GET', '/api/contacts?with=contactfields');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureContactWithContactFields,
            ],
        ]);

        $response->assertJsonFragment([
            'id' => $contactField->id,
            'object' => 'contactfield',
            'account' => [
                'id' => $user->account_id,
            ],
        ]);
    }

    public function test_it_gets_list_of_contacts_with_parameter_and_limit_and_page()
    {
        $user = $this->signin();

        $initialContact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $initialContactField = factory(ContactField::class)->create([
            'contact_id' => $initialContact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
        ]);

        $counter = 1;
        while ($counter < 12) {
            $contact = factory(Contact::class)->create([
                'account_id' => $user->account_id,
                'first_name' => 'roger',
            ]);

            $field = factory(ContactFieldType::class)->create([
                'account_id' => $user->account_id,
            ]);

            $contactField = factory(ContactField::class)->create([
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
                'contact_field_type_id' => $field->id,
            ]);

            $counter++;
        }

        $response = $this->json('GET', '/api/contacts?with=contactfields&page=1&limit=10');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureContactWithContactFields,
            ],
        ]);

        $response->assertJsonFragment([
            'id' => $initialContact->id,
            'object' => 'contactfield',
            'account' => [
                'id' => $user->account_id,
            ],
        ]);
    }

    public function test_contact_query_injection()
    {
        $firstuser = $this->signin();
        $firstcontact = factory(Contact::class)->create([
            'account_id' => $firstuser->account->id,
            'first_name' => 'Bad',
        ]);

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $response = $this->json('GET', "/api/contacts?with=contactfields&page=1&limit=100&query=1')%20or%20('%'='");

        $response->assertStatus(200);
        // Assure that firstcontact from other account is not get (SQL injection)
        $response->assertJsonMissing([
            'id' => $firstcontact->id,
            'first_name' => 'Bad',
            'account' => [
                'id' => $firstuser->account->id,
            ],
        ]);
    }

    public function test_contact_update_birthdate()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertOk();

        $response->assertJsonFragment([
            'birthdate' => [
                'date' => null,
                'is_age_based' => null,
                'is_year_unknown' => null,
            ],
        ]);

        $datas = array_only($response->json()['data'], [
            'first_name',
            'last_name',
            'nickname',
            'is_partial',
            'is_dead',
            'is_starred',
        ]) + [
            'gender_id' => $contact->gender_id,
            'birthdate' => '2008-10-25',
            'birthdate_is_age_based' => false,
            'birthdate_is_year_unknown' => false,
        ];

        $response2 = $this->json('PUT', '/api/contacts/'.$contact->id, $datas);

        $response2->assertOk();
        $response2->assertJsonMissing(['error_code']);

        $response2->assertJsonFragment([
            'birthdate' => [
                'date' => '2008-10-25T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'date' => '2008-10-25',
            'is_age_based' => '0',
            'is_year_unknown' => '0',
        ]);
    }

    public function test_contact_update_birthdate_age()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id);

        $response->assertOk();

        $response->assertJsonFragment([
            'birthdate' => [
                'date' => null,
                'is_age_based' => null,
                'is_year_unknown' => null,
            ],
        ]);

        $datas = array_only($response->json()['data'], [
            'first_name',
            'last_name',
            'nickname',
            'is_partial',
            'is_dead',
            'is_starred',
        ]) + [
            'gender_id' => $contact->gender_id,
            'birthdate_age' => '18',
            'birthdate_is_age_based' => true,
            'birthdate_is_year_unknown' => false,
        ];

        $response2 = $this->json('PUT', '/api/contacts/'.$contact->id, $datas);

        $response2->assertOk();
        $response2->assertJsonMissing(['error_code']);

        $date = Carbon::create(now()->subYears(18)->year, 1, 1, 0, 0, 0);

        $response2->assertJsonFragment([
            'birthdate' => [
                'date' => DateHelper::getTimestamp($date),
                'is_age_based' => true,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'date' => $date->toDateString(),
            'is_age_based' => '1',
            'is_year_unknown' => '0',
        ]);
    }
}
