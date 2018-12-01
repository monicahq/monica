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
use App\Models\Contact\ReminderRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderRuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $reminderRule = factory(ReminderRule::class)->create(['account_id' => $account->id]);

        $this->assertTrue($reminderRule->account()->exists());
    }

    public function test_it_gets_number_of_days_before_attribute()
    {
        $reminderRule = factory(ReminderRule::class)->create(['number_of_days_before' => '14']);

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }

    public function test_it_sets_number_of_days_before_attribute()
    {
        $reminderRule = new ReminderRule;
        $reminderRule->number_of_days_before = '14';

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }

    public function test_it_toggles_the_status()
    {
        $reminderRule = new ReminderRule;
        $reminderRule->active = true;
        $reminderRule->save();

        $reminderRule->toggle();
        $this->assertFalse(
            $reminderRule->active
        );

        $reminderRule->toggle();
        $this->assertTrue(
            $reminderRule->active
        );
    }
}
