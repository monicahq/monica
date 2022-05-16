<?php

namespace Tests\Unit\Models;

use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_user()
    {
        $channel = UserNotificationChannel::factory()->create();

        $this->assertTrue($channel->user()->exists());
    }

    /** @test */
    public function it_has_many_notification_sent(): void
    {
        $channel = UserNotificationChannel::factory()->create();
        UserNotificationSent::factory()->count(2)->create([
            'user_notification_channel_id' => $channel->id,
        ]);

        $this->assertTrue($channel->userNotificationSent()->exists());
    }

    /** @test */
    public function it_has_many_contact_reminders(): void
    {
        $channel = UserNotificationChannel::factory()->create();
        $contactReminder = ContactReminder::factory()->create();
        $channel->contactReminders()->sync([$contactReminder->id => ['scheduled_at' => Carbon::now()]]);

        $this->assertTrue($channel->contactReminders()->exists());
    }
}
