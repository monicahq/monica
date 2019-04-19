<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\ReminderSent;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderSentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $reminderSent = factory(ReminderSent::class)->create([]);
        $this->assertTrue($reminderSent->account()->exists());
    }

    public function test_it_belongs_to_a_reminder()
    {
        $reminderSent = factory(ReminderSent::class)->create([]);
        $this->assertTrue($reminderSent->reminder()->exists());
    }

    public function test_it_belongs_to_a_user()
    {
        $reminderSent = factory(ReminderSent::class)->create([]);
        $this->assertTrue($reminderSent->user()->exists());
    }
}
