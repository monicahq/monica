<?php

namespace Tests\Feature;

use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'summary',
        'description',
        'happened_at',
        'activity_type',
        'attendees' => [
            'total',
            'contacts',
        ],
        'emotions',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonStructureContacts = [
        'id',
        'name',
    ];

    protected $jsonActivity = [
        'id',
        'object',
        'summary',
        'description',
        'happened_at',
        'activity_type' => [
            'id',
            'object',
            'name',
            'location_type',
            'activity_type_category' => [
                'id',
                'object',
                'name',
                'account' => [
                    'id',
                ],
                'created_at',
                'updated_at',
            ],
            'account'=> [
                'id',
            ],
            'created_at',
            'updated_at',
        ],
        'attendees' => [
            'total',
            'contacts' => [
                '*' => [
                    'id',
                    'object',
                    'first_name',
                    'last_name',
                    'complete_name',
                ],
            ],
        ],
        'emotions' => [
            '*' => [
                'id',
                'object',
                'name',
            ],
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonActivityNoCategory = [
        'id',
        'object',
        'summary',
        'description',
        'happened_at',
        'attendees' => [
            'total',
            'contacts' => [
                '*' => [
                    'id',
                    'object',
                    'first_name',
                    'last_name',
                    'complete_name',
                ],
            ],
        ],
        'emotions' => [
            '*' => [
                'id',
                'object',
                'name',
            ],
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createActivityAndAttachToContact(User $user, Contact $contact)
    {
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity->contacts()->syncWithoutDetaching([$contact->id => [
            'account_id' => $activity->account_id,
        ]]);
    }

    public function test_it_gets_the_list_of_activities()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $this->createActivityAndAttachToContact($user, $contact);
        $this->createActivityAndAttachToContact($user, $contact);
        $this->createActivityAndAttachToContact($user, $contact);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/activities');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructure,
            ],
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()['data']
        );
    }

    public function test_it_gets_the_list_of_contacts_to_associate_with_the_activity()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        // also create of other contacts in the account
        factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/activities/contacts/');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonStructureContacts,
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()
        );
    }

    /** @test */
    public function activities_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonActivity,
        ]);
        $activity_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account_id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'description' => 'the description',
            'happened_at' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
    }

    /** @test */
    public function activities_create_error_wrong_parameter()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/activities', [
            'contact_id' => [$contact->id],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'summary' => ['The summary field is required.'],
                'happened_at' => ['The happened at field is required.'],
                'contacts' => ['The contacts field is required.'],
            ],
        ]);
    }

    /** @test */
    public function activities_create_error_bad_account()
    {
        $this->signin();

        $contact = factory(Contact::class)->create();

        $response = $this->json('POST', '/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Contact\\Contact] {$contact->id}",
        ]);
    }

    /** @test */
    public function activities_create_error_bad_account2()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $activityType = factory(ActivityType::class)->create();

        $response = $this->json('POST', '/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Account\\ActivityType] {$activityType->id}",
        ]);
    }

    /** @test */
    public function activities_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityNoCategory,
        ]);
        $activity_id = $response->json('data.id');
        $this->assertEquals($activity->id, $activity_id);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account_id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
    }

    /** @test */
    public function activities_update_category()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivity,
        ]);
        $activity_id = $response->json('data.id');
        $this->assertEquals($activity->id, $activity_id);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $activity_type_id = $response->json('data.activity_type.id');
        $this->assertEquals($activityType->id, $activity_type_id);
        $response->assertJsonFragment([
            'object' => 'activityType',
            'id' => $activity_type_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account_id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
    }

    /** @test */
    public function activities_update_existing()
    {
        $user = $this->signin();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact->activities()->attach($activity, [
            'account_id' => $user->account_id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact2->activities()->attach($activity, [
            'account_id' => $user->account_id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
            'activity_id' => $activity->id,
        ]);

        $response = $this->json('PUT', '/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityNoCategory,
        ]);
        $activity_id = $response->json('data.id');
        $this->assertEquals($activity->id, $activity_id);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account_id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
        $this->assertDatabaseMissing('activity_contact', [
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
            'activity_id' => $activity_id,
        ]);
    }

    /** @test */
    public function activities_update_error_wrong_parameter()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/activities/0', [
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'No query results for model [App\\Models\\Account\\Activity] 0',
        ]);
    }

    /** @test */
    public function activities_update_error_wrong_account_for_activity()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity = factory(Activity::class)->create();

        $response = $this->json('PUT', '/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Account\\Activity] {$activity->id}",
        ]);
    }

    /** @test */
    public function activities_update_error_wrong_account_for_contacts()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Contact\\Contact] {$contact->id}",
        ]);
    }

    /** @test */
    public function activities_delete()
    {
        $user = $this->signin();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('DELETE', '/activities/'.$activity->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('activities', [
            'account_id' => $user->account_id,
            'id' => $activity->id,
        ]);
    }

    /** @test */
    public function activities_delete_error()
    {
        $this->signin();

        $response = $this->json('DELETE', '/activities/0');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'No query results for model [App\\Models\\Account\\Activity] 0',
        ]);
    }

    /** @test */
    public function activities_delete_with_wrong_account()
    {
        $this->signin();
        $activity = factory(Activity::class)->create();

        $response = $this->json('DELETE', '/activities/'.$activity->id);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => "No query results for model [App\\Models\\Account\\Activity] {$activity->id}",
        ]);
    }
}
