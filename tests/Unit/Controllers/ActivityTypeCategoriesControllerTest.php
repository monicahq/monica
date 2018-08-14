<?php

namespace Tests\Unit\Controllers;

use Tests\FeatureTestCase;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypeCategoriesControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureActivityTypeCategory = [
        'id',
        'name',
    ];

    public function test_it_gets_activity_type_categories()
    {
        $user = $this->signin();

        $activityTypeCategories = factory(ActivityTypeCategory::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/settings/personalization/activitytypecategories');

        $response->assertJsonStructure([
            '*' => $this->jsonStructureActivityTypeCategory,
        ]);
    }

    public function test_it_stores_a_activity_type_category()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/settings/personalization/activitytypecategories', [
                            'name' => 'Movies',
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_type_categories', [
            'name' => 'Movies',
        ]);
    }

    public function test_it_updates_a_activity_type_category()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/settings/personalization/activitytypecategories', [
                            'id' => $activityTypeCategory->id,
                            'name' => 'Movies',
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_type_categories', [
            'name' => 'Movies',
        ]);
    }

    public function test_it_deletes_a_activity_type_category()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('DELETE', '/settings/personalization/activitytypecategories/'.$activityTypeCategory->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('activity_type_categories', [
            'name' => 'Movies',
        ]);
    }
}
