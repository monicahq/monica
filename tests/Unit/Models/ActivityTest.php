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

    public function testGetDescriptionReturnsNullIfUndefined()
    {
        $activity = new Activity;

        $this->assertNull($activity->getDescription());
    }

    public function testGetDescriptionReturnsDescription()
    {
        $activity = new Activity;
        $activity->description = 'This is a test';

        $this->assertEquals(
            'This is a test',
            $activity->getDescription()
        );
    }

    public function testGetDateItHappenedReturnsCarbonObject()
    {
        $activity = factory(Activity::class)->make();

        $this->assertInstanceOf(Carbon::class, $activity->date_it_happened);
    }

    public function testGetTitleReturnsAString()
    {
        $type = factory(ActivityType::class)->create();

        $activity = factory(Activity::class)->create([
            'activity_type_id' => $type->id,
        ]);

        $this->assertEquals($type->key, $activity->getTitle());
    }

    public function test_get_info_for_journal_entry()
    {
        $activity = factory(Activity::class)->create();

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
            $activity->getInfoForJournalEntry()
        );
    }
}
