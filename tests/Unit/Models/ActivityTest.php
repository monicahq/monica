<?php

namespace Tests\Unit\Models;

use App\Models\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_activity_type()
    {
        $activity = Activity::factory()->create();
        $this->assertTrue($activity->activityType()->exists());
    }
}
