<?php

namespace Tests\Unit\Models;

use App\Models\UserNotificationSent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserNotificationSentTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_one_user_notification_channel()
    {
        $sent = UserNotificationSent::factory()->create();

        $this->assertTrue($sent->notificationChannel()->exists());
    }
}
