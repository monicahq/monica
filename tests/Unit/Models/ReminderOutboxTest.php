<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\ReminderOutbox;
use App\Models\Contact\ReminderRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderOutboxTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->account()->exists());
    }

    public function test_it_belongs_to_a_reminder()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->reminder()->exists());
    }
}
