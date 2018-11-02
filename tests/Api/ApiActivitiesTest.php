<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiActivitiesTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonActivityFull = [
        'id',
        'object',
        'summary',
        'description',
        'date_it_happened',
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
                ],
            ],
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonActivityLight = [
        'id',
        'object',
        'summary',
        'description',
        'date_it_happened',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_activities_get_all()
    {
        $user = $this->signin();
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/activities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonActivityLight],
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

    public function test_activities_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity1->contacts()->attach($contact1);

        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2->contacts()->attach($contact2);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/activities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonActivityFull],
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

    public function test_activities_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/activities');

        $this->expectNotFound($response);
    }

    public function test_activities_get_one()
    {
        $user = $this->signin();
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/activities/'.$activity1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityLight,
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

    public function test_activities_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/activities/0');

        $this->expectNotFound($response);
    }

    public function test_activities_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityFull,
        ]);
        $activity_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account->id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'description' => 'the description',
            'date_it_happened' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
    }

    public function test_activities_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contact_id' => [$contact->id],
        ]);

        $this->expectDataError($response, [
            'The summary field is required.',
            'The description field is required.',
            'The date it happened field is required.',
            'The contacts field is required.',
        ]);
    }

    public function test_activities_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_activities_create_error_bad_account2()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $account = factory(Account::class)->create();
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
            'activity_type_id' => $activityType->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_activities_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityFull,
        ]);
        $activity_id = $response->json('data.id');
        $this->assertEquals($activity->id, $activity_id);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account->id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
    }

    public function test_activities_update_existing()
    {
        $user = $this->signin();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact->activities()->attach($activity, [
            'account_id' => $user->account->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact2->activities()->attach($activity, [
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
            'activity_id' => $activity->id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonActivityFull,
        ]);
        $activity_id = $response->json('data.id');
        $this->assertEquals($activity->id, $activity_id);
        $response->assertJsonFragment([
            'object' => 'activity',
            'id' => $activity_id,
        ]);

        $this->assertGreaterThan(0, $activity_id);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account->id,
            'id' => $activity_id,
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'activity_id' => $activity_id,
        ]);
        $this->assertDatabaseMissing('activity_contact', [
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
            'activity_id' => $activity_id,
        ]);
    }

    public function test_activities_update_error()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/activities/0', []);

        $this->expectNotFound($response);
    }

    public function test_activities_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contacts' => [$contact->id],
            'description' => 'the description',
            'summary' => 'the activity',
            'date_it_happened' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_activities_delete()
    {
        $user = $this->signin();
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('DELETE', '/api/activities/'.$activity->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('activities', [
            'account_id' => $user->account->id,
            'id' => $activity->id,
        ]);
    }

    public function test_activities_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/activities/0');

        $this->expectNotFound($response);
    }
}
