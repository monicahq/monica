<?php

namespace Tests\Unit\Models;

use App\Models\Activity;
use App\Models\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $activityType = ActivityType::factory()->create();
        $this->assertTrue($activityType->account()->exists());
    }

    /** @test */
    public function it_has_many_call_reasons()
    {
        $activityType = ActivityType::factory()->create();
        Activity::factory(2)->create([
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertTrue($activityType->activities()->exists());
    }
}
