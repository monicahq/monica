<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
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
        $date = $activity->happened_at;

        $journalEntry = (new JournalEntry)->add($activity);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $activity->account_id,
            'date' => $date,
            'journalable_id' => $activity->id,
            'journalable_type' => 'App\Models\Account\Activity',
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
            'day' => $activity->happened_at->day,
            'day_name' => $activity->happened_at->format('D'),
            'month' => $activity->happened_at->month,
            'month_name' => strtoupper($activity->happened_at->format('M')),
            'year' => $activity->happened_at->year,
            'attendees' => $activity->getContactsForAPI(),
        ];

        $this->assertEquals(
            $data,
            $journalEntry->getObjectData()
        );
    }
}
