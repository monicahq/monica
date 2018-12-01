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
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($gender->account()->exists());
    }

    public function test_it_belongs_to_many_contacts()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender->id]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender->id]);

        $this->assertTrue($gender->contacts()->exists());
    }

    public function test_it_gets_the_gender_name()
    {
        $gender = new Gender;
        $gender->name = 'Woman';

        $this->assertEquals(
            'Woman',
            $gender->name
        );
    }
}
