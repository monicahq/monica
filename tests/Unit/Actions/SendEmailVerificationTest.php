<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendEmailVerification;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_email_that_confirms_the_address(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $result = new SendEmailVerification(user: $user)->execute();

        $this->assertInstanceOf(User::class, $result);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            fn (VerifyEmail $notification): bool => $notification->queue === 'high',
        );
    }
}
