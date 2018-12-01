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


namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SearchHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_searching_for_contacts_returns_a_collection_with_pagination()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $searchResults = SearchHelper::searchContacts($contact->first_name, 1, 'created_at');

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(1, $searchResults);
    }

    public function test_searching_with_wrong_search_field()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $searchResults = SearchHelper::searchContacts('wrongsearchfield:1', 1, 'created_at');

        $this->assertNotNull($searchResults);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $searchResults);
        $this->assertCount(0, $searchResults);
    }
}
