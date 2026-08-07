<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendEmailVerification;
use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_email_that_confirms_the_address(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $result = new SendEmailVerification(user: $user)->execute();

        $this->assertInstanceOf(User::class, $result);

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::EmailVerification
                && $job->user->id === $user->id
                && $job->company->id === $user->company_id,
        );
    }

    #[Test]
    public function it_signs_the_link_so_it_cannot_be_forged(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        new SendEmailVerification(user: $user)->execute();

        Queue::assertPushed(
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => str_contains($job->mailable->url, 'signature=')
                && str_contains($job->mailable->url, '/verify-email/'.$user->id.'/'.sha1((string) $user->email)),
        );
    }
}
