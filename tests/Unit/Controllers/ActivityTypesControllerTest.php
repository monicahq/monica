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
use App\Models\Account\Account;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ActivityTypeCategory;
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
            'account_id' => $user->account->id,
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

    public function test_activity_type_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/settings/personalization/activitytypes', [
                            'name' => 'Movies',
                            'activity_type_category_id' => $activityTypeCategory->id,
                        ]);

        $this->expectNotFound($response);

        $this->assertDatabaseMissing('activity_types', [
            'name' => 'Movies',
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);
    }

    public function test_it_updates_a_activity_type()
    {
        $user = $this->signin();

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $user->account->id,
        ]);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $response = $this->json('PUT', '/settings/personalization/activitytypes/'.$activityType->id, [
                            'name' => 'Movies',
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Movies',
        ]);
    }
}
