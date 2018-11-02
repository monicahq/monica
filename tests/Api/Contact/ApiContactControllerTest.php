<?php

namespace Tests\Api\Contact;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Helpers\DateHelper;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
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
            'career' => [
                'job',
                'company',
                'linkedin_profile_url',
            ],
            'avatar' => [
                'url',
                'source',
                'default_avatar_color',
            ],
            'food_preferencies',
            'how_you_met' => [
                'general_information',
                'first_met_date' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
                'first_met_through_contact',
            ],
        ],
        'addresses' => [],
        'tags' => [],
        'statistics' => [
            'number_of_calls',
            'number_of_notes',
            'number_of_activities',
            'number_of_reminders',
            'number_of_tasks',
            'number_of_gifts',
            'number_of_debts',
        ],
        'contactFields' => [
            '*' => [
                'id',
                'object',
                'content',
                'contact_field_type' => [
                    'id',
                    'object',
                    'name',
                    'fontawesome_icon',
                    'protocol',
                    'delible',
                    'type',
                    'account' => [
                        'id',
                    ],
                    'created_at',
                    'updated_at',
                ],
                'account' => [
                    'id',
                ],
                'contact' => [],
                'created_at',
                'updated_at',
            ],
        ],
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
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);

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

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);

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

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);
        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/contacts?limit=2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);
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
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);

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
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);

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
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContact],
        ]);

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
        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);

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
            'data' => ['*' => $this->jsonStructureContactWithContactFields],
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
            'data' => ['*' => $this->jsonStructureContactWithContactFields],
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

    public function test_contact_get_withcontactfields()
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

        $response = $this->json('GET', '/api/contacts/'.$contact->id.'?with=contactfields');

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContactWithContactFields,
        ]);

        $response->assertJsonFragment([
            'birthdate' => [
                'date' => null,
                'is_age_based' => null,
                'is_year_unknown' => null,
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $contactField->id,
            'object' => 'contactfield',
            'content' => 'john@doe.com',
        ]);
        $response->assertJsonFragment([
            'id' => $field->id,
            'object' => 'contactfieldtype',
            'name' => 'Email',
        ]);
    }

    public function test_contact_field_query_all_account()
    {
        $firstuser = $this->signin();
        $firstcontact = factory(Contact::class)->create([
            'account_id' => $firstuser->account->id,
            'first_name' => 'Bad',
        ]);
        $firstfield = factory(ContactFieldType::class)->create([
            'account_id' => $firstuser->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $firstcontact->id,
            'account_id' => $firstuser->account_id,
            'contact_field_type_id' => $firstfield->id,
        ]);

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
        ]);

        $response = $this->json('GET', '/api/contacts?with=contactfields&page=1&limit=100&query=email:john@doe');

        $response->assertStatus(200);
        // Assure that firstcontact from other account is not get (wrong filter on account id)
        $response->assertJsonMissing([
            'id' => $firstcontact->id,
            'first_name' => 'Bad',
            'account' => [
                'id' => $firstuser->account->id,
            ],
        ]);
    }

    public function test_contact_query_internationalphone()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Phone',
            'protocol' => 'tel:',
            'type' => 'phone',
            ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
            'data' => '+447007007007',
        ]);

        $response = $this->json('GET', '/api/contacts?query=Phone:%2B447007007007');

        $response->assertStatus(200);
        $response->assertJsonFragment([
                'id' => $contact->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'account' => [
                    'id' => $user->account->id,
                ],
        ]);
    }

    public function test_contact_create()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
    }

    public function test_contact_create_error()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->expectDataError($response, [
            'The gender id field is required.',
            'The is starred field is required.',
            'The is partial field is required.',
            'The is dead field is required.',
        ]);
    }

    public function test_contact_create_first_met()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactFirstMet = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_through_contact_id' => $contactFirstMet->id,
            'first_met_information' => 'bla bla',
            'first_met_date' => '2002-01-01',
            'first_met_date_is_age_based' => false,
            'first_met_date_is_year_unknown' => false,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'first_met_date' => [
                'date' => '2002-01-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_additional_info' => 'bla bla',
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->first_met_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '2002-01-01',
        ]);
    }

    public function test_contact_create_first_met_bad_account()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $account = factory(Account::class)->create();
        $contactFirstMet = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_through_contact_id' => $contactFirstMet->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_contact_create_birthdate()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate' => '1980-05-01',
            'birthdate_is_year_unknown' => false,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '1980-05-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->birthday_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '1980-05-01',
        ]);
    }

    public function test_contact_create_birthdate_year_unknown()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate' => '2010-05-01',
            'birthdate_is_year_unknown' => true,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '2017-05-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => true,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->birthday_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => true,
            'date' => '2017-05-01',
        ]);
    }

    public function test_contact_create_birthdate_age_based()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate_is_age_based' => true,
            'birthdate_age' => '37',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '1980-01-01T00:00:00Z',
                'is_age_based' => true,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->birthday_special_date_id,
            'is_age_based' => true,
            'is_year_unknown' => false,
            'date' => '1980-01-01',
        ]);
    }

    public function test_contact_create_deceased_date()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date' => '2009-06-25',
            'deceased_date_is_year_unknown' => false,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2009-06-25T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->deceased_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '2009-06-25',
        ]);
    }

    public function test_contact_create_deceased_date_year_unknown()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date' => '2009-06-25',
            'deceased_date_is_year_unknown' => true,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2017-06-25T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => true,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->deceased_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => true,
            'date' => '2017-06-25',
        ]);
    }

    public function test_contact_create_deceased_date_age_based()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 6, 25, 7, 0, 0));

        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/', [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date_age' => '8',
            'deceased_date_is_age_based' => true,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $contact_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact_id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2009-01-01T00:00:00Z',
                'is_age_based' => true,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertGreaterThan(0, $contact_id);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact_id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact_id,
            'id' => Contact::find($contact_id)->deceased_special_date_id,
            'is_age_based' => true,
            'is_year_unknown' => false,
            'date' => '2009-01-01',
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

    public function test_contact_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
    }

    public function test_contact_update_bad_account()
    {
        $user = $this->signin();
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contact = factory(Contact::class)->create();

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);

        $this->expectNotFound($response);
    }

    public function test_contact_update_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $this->expectDataError($response, [
            'The gender id field is required.',
            'The is starred field is required.',
            'The is partial field is required.',
            'The is dead field is required.',
        ]);
    }

    public function test_contact_update_first_met()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactFirstMet = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_through_contact_id' => $contactFirstMet->id,
            'first_met_information' => 'bla bla',
            'first_met_date' => '2002-01-01',
            'first_met_date_is_age_based' => false,
            'first_met_date_is_year_unknown' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'first_met_date' => [
                'date' => '2002-01-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_additional_info' => 'bla bla',
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->first_met_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '2002-01-01',
        ]);
    }

    public function test_contact_update_first_met_bad_account()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $account = factory(Account::class)->create();
        $contactFirstMet = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'first_met_through_contact_id' => $contactFirstMet->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_contact_update_birthdate()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate' => '1980-05-01',
            'birthdate_is_year_unknown' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '1980-05-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->birthday_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '1980-05-01',
        ]);
    }

    public function test_contact_update_birthdate_year_unknown()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate' => '2010-05-01',
            'birthdate_is_year_unknown' => true,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '2017-05-01T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => true,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->birthday_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => true,
            'date' => '2017-05-01',
        ]);
    }

    public function test_contact_update_birthdate_age_based()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
            'birthdate_is_age_based' => true,
            'birthdate_age' => '37',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'birthdate' => [
                'date' => '1980-01-01T00:00:00Z',
                'is_age_based' => true,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->birthday_special_date_id,
            'is_age_based' => true,
            'is_year_unknown' => false,
            'date' => '1980-01-01',
        ]);
    }

    public function test_contact_update_deceased_date()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date' => '2009-06-25',
            'deceased_date_is_year_unknown' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2009-06-25T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->deceased_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '2009-06-25',
        ]);
    }

    public function test_contact_update_deceased_date_year_unknown()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 2, 3, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date' => '2009-06-25',
            'deceased_date_is_year_unknown' => true,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2017-06-25T00:00:00Z',
                'is_age_based' => false,
                'is_year_unknown' => true,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->deceased_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => true,
            'date' => '2017-06-25',
        ]);
    }

    public function test_contact_update_deceased_date_age_based()
    {
        // The year is used to set the date in database
        Carbon::setTestNow(Carbon::create(2017, 6, 25, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id, [
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
            'deceased_date_age' => '8',
            'deceased_date_is_age_based' => true,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContact,
        ]);
        $response->assertJsonFragment([
            'object' => 'contact',
            'id' => $contact->id,
        ]);
        $response->assertJsonFragment([
            'deceased_date' => [
                'date' => '2009-01-01T00:00:00Z',
                'is_age_based' => true,
                'is_year_unknown' => false,
            ],
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'id' => $contact->id,
            'first_name' => 'Michael',
            'last_name' => 'Jackson',
            'gender_id' => $gender->id,
            'is_starred' => false,
            'is_partial' => false,
            'is_dead' => true,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->deceased_special_date_id,
            'is_age_based' => true,
            'is_year_unknown' => false,
            'date' => '2009-01-01',
        ]);
    }
}
