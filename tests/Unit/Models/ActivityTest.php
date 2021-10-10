<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_happened_at()
    {
        $activity = factory(Activity::class)->make();

        $this->assertInstanceOf(
            Carbon::class,
            $activity->happened_at
        );
    }

    /** @test */
    public function it_returns_a_title()
    {
        $type = factory(ActivityType::class)->create();

        $activity = factory(Activity::class)->create([
            'activity_type_id' => $type->id,
        ]);

        $this->assertEquals(
            $type->translation_key,
            $activity->getTitle()
        );
    }

    /** @test */
    public function it_gets_info_for_journal_entry()
    {
        $activity = factory(Activity::class)->create();

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
            $activity->getInfoForJournalEntry()
        );
    }
}
