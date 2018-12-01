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
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LIfeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $this->assertTrue($lifeEventType->account()->exists());
    }

    public function test_it_belongs_to_a_category()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $this->assertTrue($lifeEventType->lifeEventCategory()->exists());
    }

    public function test_it_has_many_life_events()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $lifeEventType = factory(LifeEventType::class)->create([]);
        $lifeEvents = factory(LifeEvent::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->assertTrue($lifeEventType->lifeEvents()->exists());
    }

    public function test_it_gets_the_name_attribute()
    {
        $lifeEventType = factory(LifeEventType::class)->create([
            'name' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $lifeEventType->name
        );
    }
}
