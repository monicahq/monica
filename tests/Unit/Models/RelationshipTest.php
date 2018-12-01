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
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationship->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'contact_is' => $contact->id,
        ]);

        $this->assertTrue($relationship->contactIs()->exists());
    }

    public function test_it_belongs_to_another_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }

    public function test_it_belongs_to_a_relationship_type()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        $this->assertTrue($relationship->relationshipType()->exists());
    }

    public function test_it_belongs_to_a_contact_through_with_contact_field()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }
}
