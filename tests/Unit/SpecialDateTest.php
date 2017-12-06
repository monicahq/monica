<?php

namespace Tests\Unit;

use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use Tests\TestCase;
use App\SpecialDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpecialDateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_delete_reminder_returns_null_if_no_reminder_is_set()
    {
        $specialDate = new SpecialDate;


        $this->assertNull($contact->deleteReminder());
    }

    public function test_delete_reminder_destroys_the_associated_reminder()
    {
        $reminder = new Reminder;
        $reminder->id = 1;

        $specialDate = new SpecialDate;
        $specialDate->reminder_id = $reminder->id;

        $this->assertEquals(
            true,
            $specialDate->deleteReminder()
        );
    }
}
