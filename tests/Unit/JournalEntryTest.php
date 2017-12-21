<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class JournalEntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_add_adds_data_of_the_right_type()
    {
        $activity = factory(\App\Activity::class)->create();
        $date = \Carbon\Carbon::now();

        $journalEntry = (new JournalEntry)->add($activity);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => 1,
            'date' => $date,
            'journalable_id' => $activity->id,
            'journalable_type' => 'App\Activity',
        ]);
    }

    public function test_get_object_data_returns_an_object()
    {
        $activity = factory(\App\Activity::class)->create();
        $date = \Carbon\Carbon::now();

        $journalEntry = (new JournalEntry)->add($activity);

        $data = [
            'type' => 'activity',
            'id' => $activity->id,
            'activity_type' => (! is_null($activity->type) ? $activity->type->getTranslationKeyAsString() : null),
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
