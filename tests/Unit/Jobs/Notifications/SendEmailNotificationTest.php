<?php

namespace Tests\Unit\Jobs\Notifications;

use Tests\TestCase;
use App\Mail\SendReminder;
use Illuminate\Support\Facades\Mail;
use App\Models\UserNotificationChannel;
use App\Models\ScheduledContactReminder;
use App\Jobs\Notifications\SendEmailNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendEmailNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_a_notification_by_email(): void
    {
        Mail::fake();

        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'user_notification_channel_id' => $channel->id,
        ]);

        SendEmailNotification::dispatch($scheduledContactReminder);

        Mail::assertQueued(SendReminder::class);

        $this->assertDatabaseHas('user_notification_sent', [
            'user_notification_channel_id' => $channel->id,
            'sent_at' => now(),
            'subject_line' => $scheduledContactReminder->reminder->label,
        ]);
    }
}
