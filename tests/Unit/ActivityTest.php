<?php

namespace Tests\Unit;

use App\Activity;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
        $activity = factory(\App\Activity::class)->make();

        $this->assertInstanceOf(Carbon::class, $activity->getDateItHappened());
    }

    public function testGetTitleReturnsAString()
    {
        $type = factory(\App\ActivityType::class)->create();

        $activity = factory(\App\Activity::class)->create([
            'activity_type_id' => $type->id
        ]);

        $this->assertEquals($type->key, $activity->getTitle());
    }
}
