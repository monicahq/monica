<?php

namespace Tests\Unit;

use App\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
        $activity = factory(\App\Activity::class)->make();

        $this->assertInstanceOf(Carbon::class, $activity->getDateItHappened());
    }

    public function testGetTitleReturnsAString()
    {
        $activity = new Activity;
        $activity->activity_type_id = 1;

        $this->assertInternalType('string', $activity->getTitle());
    }
}
