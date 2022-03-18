<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\ContactReminder;
use App\Models\UserNotificationSent;
use App\Models\UserNotificationChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
