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


namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->account()->exists());
    }

    public function test_it_belongs_to_a_category()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->category()->exists());
    }

    public function test_it_has_many_activities()
    {
        $account = factory(Account::class)->create();
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);
        $activity = factory(Activity::class, 2)->create([
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertTrue($account->activities()->exists());
    }

    public function test_it_gets_the_name_attribute()
    {
        $activityType = factory(ActivityType::class)->create([
            'translation_key' => 'awesome_key',
            'name' => null,
        ]);

        $this->assertEquals(
            'people.activity_type_awesome_key',
            $activityType->name
        );

        $activityType = factory(ActivityType::class)->create([
            'translation_key' => null,
            'name' => 'awesome_name',
        ]);

        $this->assertEquals(
            'awesome_name',
            $activityType->name
        );
    }

    public function test_it_resets_the_associated_activities()
    {
        $activityType = factory(ActivityType::class)->create([]);
        $activity = factory(Activity::class, 10)->create([
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertEquals(
            10,
            $activityType->activities()->count()
        );

        $this->assertDatabaseHas('activities', [
            'activity_type_id' => $activityType->id,
        ]);

        $activityType->resetAssociationWithActivities();

        $this->assertDatabaseMissing('activities', [
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertEquals(
            0,
            $activityType->activities()->count()
        );
    }
}
