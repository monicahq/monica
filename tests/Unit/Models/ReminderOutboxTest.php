<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderOutboxTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_reminder()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->reminder()->exists());
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->user()->exists());
    }
}
