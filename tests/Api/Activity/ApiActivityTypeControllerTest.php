<?php

namespace Tests\Api\Activity;

use Tests\ApiTestCase;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiActivityTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureActivityType = [
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
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_activity_types()
    {
        $user = $this->signin();

        $activityTypes = factory(ActivityType::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activitytypes');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureActivityType,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $activityTypes = factory(ActivityType::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activitytypes?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/activitytypes?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_stores_an_activity_type()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/activitytypes', [
                            'name' => 'Movies',
                            'activity_type_category_id' => $activityTypeCategory->id,
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Movies',
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityType,
        ]);
    }

    public function test_it_doesnt_store_an_activity_type_if_query_not_valid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/activitytypes');

        $this->expectDataError($response, [
            'The name field is required.',
            'The activity type category id field is required.',
        ]);
    }

    public function test_it_updates_an_activity_type()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response = $this->json('PUT', '/api/activitytypes/'.$activityType->id, [
                            'name' => 'Movies',
                            'activity_type_category_id' => $activityTypeCategory->id,
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Movies',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityType,
        ]);
    }

    public function test_it_doesnt_update_if_activity_type_not_found()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/activitytypes/2349273984279348', [
                            'name' => 'Movies',
                        ]);

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_doesnt_update_if_query_is_invalid()
    {
        $user = $this->signin();

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
        ]);

        $response = $this->json('PUT', '/api/activitytypes/'.$activityType->id, [
            'activity_type_category_id' => 3,
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_it_deletes_an_activity_type()
    {
        $user = $this->signin();

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activities = factory(Activity::class, 10)->create([
            'account_id' => $user->account_id,
            'activity_type_id' => $activityType->id,
        ]);

        $response = $this->delete('/api/activitytypes/'.$activityType->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('activity_types', [
            'id' => $activityType->id,
        ]);

        $this->assertDatabaseMissing('activities', [
            'activity_type_id' => $activityType->id,
        ]);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $activityType->id,
        ]);
    }

    public function test_it_doesnt_delete_the_activity_type_if_not_found()
    {
        $user = $this->signin();

        $response = $this->delete('/api/activitytypes/2349273984279348');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_a_single_activity_type()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response = $this->json('GET', '/api/activitytypes/'.$activityType->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityType,
        ]);
    }
}
