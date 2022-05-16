<?php

namespace Tests\Unit\Jobs\Notifications;

use App\Jobs\Notifications\SendEmailNotification;
use App\Mail\SendReminder;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendEmailNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_a_notification_by_email(): void
    {
        Mail::fake();

        $userNotificationChannel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $contactReminder = ContactReminder::factory()->create();

        SendEmailNotification::dispatch(
            $userNotificationChannel->id,
            $contactReminder->id
        );

        Mail::assertQueued(SendReminder::class);

        $this->assertDatabaseHas('user_notification_sent', [
            'user_notification_channel_id' => $userNotificationChannel->id,
            'sent_at' => now(),
            'subject_line' => $contactReminder->label,
        ]);
    }
}
