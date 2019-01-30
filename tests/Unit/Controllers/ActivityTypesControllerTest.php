<?php

namespace Tests\Unit\Controllers;

use Tests\FeatureTestCase;
use App\Models\Account\ActivityType;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypesControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureActivityType = [
        'id',
        'name',
        'location_type',
    ];

    public function test_it_stores_a_activity_type()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/settings/personalization/activitytypes', [
                            'name' => 'Movies',
                            'activity_type_category_id' => $activityTypeCategory->id,
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Movies',
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);
    }

    public function test_it_updates_a_activity_type()
    {
        $user = $this->signin();

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/settings/personalization/activitytypes/'.$activityType->id, [
            'name' => 'Movies',
            'activity_type_category_id' => $activityType->activity_type_category_id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Movies',
        ]);
    }
}
