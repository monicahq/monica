<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Journal\Entry;
use App\Models\Account\Account;
use App\Models\Account\Activity;
use App\Models\Journal\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class JournalEntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $task = factory(JournalEntry::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($task->account()->exists());
    }

    public function test_it_has_polymorphic_relations()
    {
        $activity = factory(Activity::class)->create();
        $journalEntry = JournalEntry::add($activity);
        $activity->refresh();

        $this->assertNotNull($journalEntry->journalable);
        $this->assertEquals($activity->id, $journalEntry->journalable->id);
        $this->assertNotNull($activity->journalEntry);
        $this->assertEquals($journalEntry->id, $activity->journalEntry->id);
    }

    public function test_it_has_polymorphic_relations2()
    {
        $entry = factory(Entry::class)->create();
        $entry->date = '2018-01-01';
        $journalEntry = JournalEntry::add($entry);
        $entry->refresh();

        $this->assertNotNull($journalEntry->journalable);
        $this->assertEquals($entry->id, $journalEntry->journalable->id);
        $this->assertNotNull($entry->journalEntry);
        $this->assertEquals($journalEntry->id, $entry->journalEntry->id);
    }

    public function test_get_add_adds_data_of_the_right_type()
    {
        $activity = factory(Activity::class)->create();
        $date = $activity->date_it_happened;

        $journalEntry = JournalEntry::add($activity);

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

        $journalEntry = JournalEntry::add($activity);

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

    public function test_get_edit_journal_entry()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 0, 0, 0));

        $entry = factory(Entry::class)->create([
            'title' => 'This is the title',
            'post' => 'this is a post',
        ]);
        $entry->date = '2017-01-01';
        $journalEntry = JournalEntry::add($entry);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $entry->account_id,
            'date' => '2017-01-01 00:00:00',
            'journalable_id' => $entry->id,
            'journalable_type' => 'App\Models\Journal\Entry',
        ]);

        $entry->date = '2018-01-01';
        $journalEntry->edit($entry);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $entry->account_id,
            'date' => '2018-01-01 00:00:00',
            'journalable_id' => $entry->id,
            'journalable_type' => 'App\Models\Journal\Entry',
        ]);
    }
}
