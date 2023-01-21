<?php

namespace Tests\Unit\Models;

use App\Models\MoodTrackingEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MoodTrackingEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $event = MoodTrackingEvent::factory()->create();

        $this->assertTrue($event->contact()->exists());
    }

    /** @test */
    public function it_has_one_parameter()
    {
        $event = MoodTrackingEvent::factory()->create();

        $this->assertTrue($event->moodTrackingParameter()->exists());
    }
}
