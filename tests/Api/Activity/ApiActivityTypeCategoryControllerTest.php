<?php

namespace Tests\Api\Activity;

use Tests\ApiTestCase;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiActivityTypeCategoryControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureActivityTypeCategory = [
        'id',
        'object',
        'name',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_activity_type_categories()
    {
        $user = $this->signin();

        $activityTypeCategories = factory(ActivityTypeCategory::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activitytypecategories');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureActivityTypeCategory,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $activityTypeCategories = factory(ActivityTypeCategory::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activitytypecategories?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/activitytypecategories?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_stores_a_activity_type_category()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/activitytypecategories', [
                            'name' => 'Movies',
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('activity_type_categories', [
            'name' => 'Movies',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityTypeCategory,
        ]);
    }

    public function test_it_doesnt_store_an_activity_type_category_if_query_not_valid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/activitytypecategories');

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_it_updates_a_activity_type_category()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/activitytypecategories/'.$activityTypeCategory->id, [
            'name' => 'Movies',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_type_categories', [
            'name' => 'Movies',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityTypeCategory,
        ]);
    }

    public function test_it_doesnt_update_if_custom_field_not_found()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/activitytypecategories/2349273984279348', [
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

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
        ]);

        $response = $this->json('PUT', '/api/activitytypecategories/'.$activityTypeCategory->id, [
                        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_it_deletes_a_activity_type_category()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
        ]);

        $activityType = factory(ActivityType::class, 10)->create([
            'account_id' => $user->account_id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response = $this->delete('/api/activitytypecategories/'.$activityTypeCategory->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('activity_type_categories', [
            'id' => $activityTypeCategory->id,
        ]);

        $this->assertDatabaseMissing('activity_types', [
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $activityTypeCategory->id,
        ]);
    }

    public function test_it_doesnt_delete_the_custom_field_if_not_found()
    {
        $user = $this->signin();

        $response = $this->delete('/api/activitytypecategories/2349273984279348');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_a_single_activity_type_category()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/activitytypecategories/'.$activityTypeCategory->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureActivityTypeCategory,
        ]);
    }
}
