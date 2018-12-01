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
use App\Models\Contact\LifeEventType;
use App\Models\Contact\LifeEventCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LifeEventCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($lifeEventCategory->account()->exists());
    }

    public function test_it_has_many_life_event_types()
    {
        $lifeEventCategory = factory(LifeEventCategory::class)->create();
        $lifeEventType = factory(LifeEventType::class)->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);

        $this->assertTrue($lifeEventCategory->lifeEventTypes()->exists());
    }

    public function test_it_gets_name_attribute()
    {
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'name' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $lifeEventCategory->name
        );
    }
}
