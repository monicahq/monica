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
use App\Models\Journal\Entry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_info_for_journal_entry()
    {
        $entry = factory(Entry::class)->make();
        $entry->id = 1;
        $entry->title = 'This is the title';
        $entry->post = 'this is a post';
        $entry->created_at = '2017-01-01 00:00:00';
        $entry->save();

        $data = [
            'type' => 'entry',
            'id' => 1,
            'title' => 'This is the title',
            'post' => '<p>this is a post</p>',
            'day' => 1,
            'day_name' => 'Sun',
            'month' => 1,
            'month_name' => 'Jan',
            'year' => 2017,
        ];

        $this->assertEquals(
            $data,
            $entry->getInfoForJournalEntry()
        );
    }
}
