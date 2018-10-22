<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiActivitiesTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonActivity = [
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
                'id',
            ],
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_activities_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
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

    public function test_activities_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

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

    public function test_activities_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/activities');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }

    public function test_activities_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity1 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $activity2 = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
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

    public function test_activities_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/activities/0');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }

    public function test_activities_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contact_id' => $contact->id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $activity_id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
        ]);
    }

    public function test_activities_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/activities', [
            'contact_id' => $contact->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => [
                'error_code' => 32,
            ],
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
            'contact_id' => $contact->id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }

    public function test_activities_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contact_id' => $contact->id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $activity_id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
        ]);
    }

    public function test_activities_update_error()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/activities/0', []);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
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
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/activities/'.$activity->id, [
            'contact_id' => $contact->id,
            'content' => 'the activity',
            'activityed_at' => '2018-05-01',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }

    public function test_activities_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('activities', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $activity->id,
        ]);

        $response = $this->json('DELETE', '/api/activities/'.$activity->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('activities', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $activity->id,
        ]);
    }

    public function test_activities_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/activities/0');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }
}
