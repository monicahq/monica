<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
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
}
