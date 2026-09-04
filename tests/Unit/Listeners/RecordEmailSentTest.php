<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\EmailType;
use App\Listeners\RecordEmailSent;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use App\Notifications\LoginFailed;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSent;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class RecordEmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_an_email_that_went_out_into_the_journal(): void
    {
        $account = Account::factory()->create();
        $michael = User::factory()->create([
            'account_id' => $account->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $michael->notify(new LoginFailed);

        $this->assertDatabaseHas('emails_sent', [
            'account_id' => $account->id,
            'user_id' => $michael->id,
            'email_type' => EmailType::LoginFailed->value,
            'email_address' => 'michael.scott@dundermifflin.com',
            'subject' => 'Failed sign-in attempt on your '.config('app.name').' account',
            'uuid' => null,
        ]);

        $this->assertNotNull(EmailSent::query()->latest()->firstOrFail()->sent_at);
    }

    #[Test]
    public function it_keeps_what_the_reader_was_told(): void
    {
        $michael = User::factory()->create();

        $michael->notify(new LoginFailed);

        $this->assertStringContainsString(
            'change your password now',
            (string) EmailSent::query()->latest()->firstOrFail()->body,
        );
    }

    #[Test]
    public function it_reads_back_the_message_id_resend_gave_it(): void
    {
        $michael = User::factory()->create();

        new RecordEmailSent()->handle(
            new NotificationSent($michael, new LoginFailed, 'mail', $this->sentMessage('resend-uuid-12345')),
        );

        $this->assertDatabaseHas('emails_sent', [
            'user_id' => $michael->id,
            'uuid' => 'resend-uuid-12345',
        ]);
    }

    #[Test]
    public function it_writes_nothing_for_a_notification_that_does_not_belong_in_the_journal(): void
    {
        $michael = User::factory()->unverified()->create();

        $michael->notify(new VerifyEmail);

        $this->assertDatabaseCount('emails_sent', 0);
    }

    #[Test]
    public function it_writes_nothing_for_a_channel_that_is_not_email(): void
    {
        $michael = User::factory()->create();

        new RecordEmailSent()->handle(
            new NotificationSent($michael, new LoginFailed, 'database', $this->sentMessage()),
        );

        $this->assertDatabaseCount('emails_sent', 0);
    }

    #[Test]
    public function it_writes_nothing_when_no_message_came_back(): void
    {
        $michael = User::factory()->create();

        new RecordEmailSent()->handle(
            new NotificationSent($michael, new LoginFailed, 'mail', null),
        );

        $this->assertDatabaseCount('emails_sent', 0);
    }

    private function sentMessage(?string $resendId = null): SentMessage
    {
        $email = new Email()
            ->from('noreply@monica.test')
            ->to('michael.scott@dundermifflin.com')
            ->subject('Failed sign-in attempt')
            ->html('<p>Somebody tried to sign in.</p>');

        if ($resendId !== null) {
            $email->getHeaders()->addHeader('X-Resend-Email-ID', $resendId);
        }

        return new SentMessage(new SymfonySentMessage(
            $email,
            new Envelope(new Address('noreply@monica.test'), [new Address('michael.scott@dundermifflin.com')]),
        ));
    }
}
