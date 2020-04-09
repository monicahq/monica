<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiActivitiesTest extends ApiTestCase
{
    use DatabaseTransactions;

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

    /** @test */
    public function activities_get_all()
    {
        $user = $this->signin();
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonActivity],
        ]);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity2->id,
        ]);
    }

    /** @test */
    public function activities_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity1->contacts()->attach($contact1, ['account_id' => $user->account_id]);

        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity2->contacts()->attach($contact2, ['account_id' => $user->account_id]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/activities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonActivity],
        ]);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'activity',
            'id' => $activity2->id,
        ]);
    }

    /** @test */
    public function activities_get_contact_all_error()
    {
        $this->signin();

        $response = $this->json('GET', '/api/contacts/0/activities');

        $this->expectNotFound($response);
    }

    /** @test */
    public function activities_get_contact_all_error_wrong_account()
    {
        $this->signin();
        $contact = factory(Contact::class)->create();

        $response = $this->json('GET', '/api/contacts/'.$contact->id.'/activities');

        $this->expectNotFound($response);
    }

    /** @test */
    public function activities_get_one()
    {
        $user = $this->signin();
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activities/'.$activity1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivity,
        ]);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'activity',
            'id' => $activity2->id,
        ]);
    }

    /** @test */
    public function activities_get_one_error()
    {
        $this->signin();

        $response = $this->json('GET', '/api/activities/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function activities_get_one_error_wrong_account()
    {
        $this->signin();
        $activity = factory(Activity::class)->create();

        $response = $this->json('GET', '/api/activities/'.$activity->id);

        $this->expectNotFound($response);
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

        $response = $this->json('POST', '/api/activities', [
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

        $response = $this->json('POST', '/api/activities', [
            'contact_id' => [$contact->id],
        ]);

        $this->expectDataError($response, [
            'The summary field is required.',
            'The happened at field is required.',
        ]);
    }

    /** @test */
    public function activities_create_error_bad_account()
    {
        $this->signin();

        $contact = factory(Contact::class)->create();

        $response = $this->json('POST', '/api/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function activities_create_error_bad_account2()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $activityType = factory(ActivityType::class)->create();

        $response = $this->json('POST', '/api/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $this->expectNotFound($response);
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

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
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

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
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

        $response = $this->json('PUT', '/api/activities/0', [
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $this->expectDataError($response, [
            'The selected activity id is invalid.',
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

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function activities_update_error_wrong_account_for_contacts()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'happened_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
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

        $response = $this->json('DELETE', '/api/activities/'.$activity->id);

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

        $response = $this->json('DELETE', '/api/activities/0');

        $this->expectDataError($response, [
            'The selected activity id is invalid.',
        ]);
    }

    /** @test */
    public function activities_delete_with_wrong_account()
    {
        $this->signin();
        $activity = factory(Activity::class)->create();

        $response = $this->json('DELETE', '/api/activities/'.$activity->id);

        $this->expectNotFound($response);
    }
}
