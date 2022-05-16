<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Jobs;

use App\Mail\UserNotificationChannelEmailCreated;
use App\Models\UserNotificationChannel;
use App\Settings\ManageNotificationChannels\Jobs\SendVerificationEmailChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

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
