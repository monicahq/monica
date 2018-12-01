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
use App\Models\Contact\Pet;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $pet = factory(Pet::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($pet->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $pet = factory(Pet::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($pet->contact()->exists());
    }

    public function test_it_belongs_to_a_pet_category()
    {
        $petCategory = factory(PetCategory::class)->create([]);
        $pet = factory(Pet::class)->create([
            'pet_category_id' => $petCategory->id,
        ]);

        $this->assertTrue($pet->petCategory()->exists());
    }

    public function test_it_sets_name()
    {
        $pet = new Pet;
        $this->assertNull($pet->name);

        $pet->name = 'henri';
        $this->assertEquals(
            'henri',
            $pet->name
        );
    }
}
