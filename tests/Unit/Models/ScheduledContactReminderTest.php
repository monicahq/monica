<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ScheduledContactReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ScheduledContactReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact_reminder()
    {
        $scheduledReminder = ScheduledContactReminder::factory()->create();

        $this->assertTrue($scheduledReminder->reminder()->exists());
    }

    /** @test */
    public function it_has_one_user()
    {
        $scheduledReminder = ScheduledContactReminder::factory()->create();

        $this->assertTrue($scheduledReminder->user()->exists());
    }
}
