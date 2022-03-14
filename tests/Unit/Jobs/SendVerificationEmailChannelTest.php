<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Models\UserNotificationChannel;
use App\Jobs\SendVerificationEmailChannel;
use App\Mail\UserNotificationChannelEmailCreated;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendVerificationEmailChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_a_verification_email(): void
    {
        Mail::fake();

        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        SendVerificationEmailChannel::dispatch($channel);

        Mail::assertSent(UserNotificationChannelEmailCreated::class, function ($mail) {
            return $mail->hasTo('admin@admin.com');
        });
    }
}
