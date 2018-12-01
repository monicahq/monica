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
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SearchableTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function testSearchContactsReturnsCollection()
    {
        $contact = factory(Contact::class)->make();
        $searchResults = $contact->search($contact->first_name, $contact->account_id, 10, 'created_at desc');

        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
    }

    /** @test */
    public function testSearchContactsThroughFirstNameAndResultContainsContact()
    {
        $contact = factory(Contact::class)->create(['first_name' => 'FirstName']);
        $searchResults = $contact->search($contact->first_name, $contact->account_id, 10, 'created_at desc');

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughMiddleNameAndResultContainsContact()
    {
        $contact = factory(Contact::class)->create(['middle_name' => 'MiddleName']);
        $searchResults = $contact->search($contact->middle_name, $contact->account_id, 10, 'created_at desc');

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughLastNameAndResultContainsContact()
    {
        $contact = factory(Contact::class)->create(['last_name' => 'LastName']);

        $searchResults = $contact->search($contact->last_name, $contact->account_id, 10, 'created_at desc');

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testFailingSearchContacts()
    {
        $contact = factory(Contact::class)->create(['first_name' => 'TestShouldFail']);
        $searchResults = $contact->search('TestWillSucceed', $contact->account_id, 10, 'created_at desc');

        $this->assertFalse($searchResults->contains($contact));
    }
}
