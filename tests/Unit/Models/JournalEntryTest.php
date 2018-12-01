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
use App\Models\Contact\Activity;
use App\Models\Journal\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class JournalEntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $task = factory(JournalEntry::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($task->account()->exists());
    }

    public function test_get_add_adds_data_of_the_right_type()
    {
        $activity = factory(Activity::class)->create();
        $date = $activity->date_it_happened;

        $journalEntry = (new JournalEntry)->add($activity);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $activity->account_id,
            'date' => $date,
            'journalable_id' => $activity->id,
            'journalable_type' => 'App\Models\Contact\Activity',
        ]);
    }

    public function test_get_object_data_returns_an_object()
    {
        $activity = factory(Activity::class)->create();

        $journalEntry = (new JournalEntry)->add($activity);

        $data = [
            'type' => 'activity',
            'id' => $activity->id,
            'activity_type' => (! is_null($activity->type) ? $activity->type->name : null),
            'summary' => $activity->summary,
            'description' => $activity->description,
            'day' => $activity->date_it_happened->day,
            'day_name' => $activity->date_it_happened->format('D'),
            'month' => $activity->date_it_happened->month,
            'month_name' => strtoupper($activity->date_it_happened->format('M')),
            'year' => $activity->date_it_happened->year,
            'attendees' => $activity->getContactsForAPI(),
        ];

        $this->assertEquals(
            $data,
            $journalEntry->getObjectData()
        );
    }
}
