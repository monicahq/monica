<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $reminder = ContactReminder::factory()->create();

        $this->assertTrue($reminder->contact()->exists());
    }
}
