<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
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

    /** @test */
    public function it_has_many_user_notification_channels(): void
    {
        $reminder = ContactReminder::factory()->create();
        $userNotificationChannel = UserNotificationChannel::factory()->create();
        $reminder->userNotificationChannels()->sync([$userNotificationChannel->id => ['scheduled_at' => Carbon::now()]]);

        $this->assertTrue($reminder->userNotificationChannels()->exists());
    }
}
