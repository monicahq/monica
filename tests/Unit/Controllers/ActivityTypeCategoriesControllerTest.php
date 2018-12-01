<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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

        $response = $this->json('PUT', '/settings/personalization/activitytypecategories/'.$activityTypeCategory->id, [
            'name' => 'Movies',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_type_categories', [
            'id' => $activityTypeCategory->id,
            'name' => 'Movies',
        ]);
    }

    public function test_activity_type_category_update_bad_account()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create();

        $response = $this->json('PUT', '/settings/personalization/activitytypecategories/'.$activityTypeCategory->id, [
            'name' => 'Movies',
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('activity_type_categories', [
            'id' => $activityTypeCategory->id,
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
