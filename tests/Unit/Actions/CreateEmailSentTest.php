<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmailSent;
use App\Enums\EmailType;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_email_sent(): void
    {
        Date::setTestNow(Date::create(2026, 7, 31));

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $emailSent = new CreateEmailSent(
            account: $account,
            user: $user,
            uuid: 'd27cee22-b10f-46c4-a7dc-af3b46820d80',
            emailType: EmailType::NewLogin->value,
            emailAddress: 'michael.scott@dundermifflin.com',
            subject: 'A new login on your account',
            body: 'Someone just signed in.',
        )->execute();

        $this->assertInstanceOf(EmailSent::class, $emailSent);
        $this->assertDatabaseHas('emails_sent', [
            'id' => $emailSent->id,
            'account_id' => $account->id,
            'user_id' => $user->id,
            'uuid' => 'd27cee22-b10f-46c4-a7dc-af3b46820d80',
            'email_type' => EmailType::NewLogin->value,
            'email_address' => 'michael.scott@dundermifflin.com',
            'subject' => 'A new login on your account',
            'sent_at' => '2026-07-31 00:00:00',
        ]);
    }

    #[Test]
    public function it_creates_an_email_sent_without_a_user(): void
    {
        $account = Account::factory()->create();

        $emailSent = new CreateEmailSent(
            account: $account,
            user: null,
            uuid: null,
            emailType: EmailType::MagicLinkCreated->value,
            emailAddress: 'jan.levinson@dundermifflin.com',
            subject: 'Your magic link',
            body: 'Here is your link.',
        )->execute();

        $this->assertDatabaseHas('emails_sent', [
            'id' => $emailSent->id,
            'account_id' => $account->id,
            'user_id' => null,
            'uuid' => null,
        ]);
    }

    #[Test]
    public function it_removes_every_web_address_from_the_copy(): void
    {
        $account = Account::factory()->create();

        $emailSent = new CreateEmailSent(
            account: $account,
            user: null,
            uuid: null,
            emailType: EmailType::MagicLinkCreated->value,
            emailAddress: 'dwight.schrute@dundermifflin.com',
            subject: 'Your sign-in link',
            body: '<p>Hi Dwight, <a href="https://monica.test/magic-link/abc?signature=1&expires=2">Sign in</a></p><p>Copy this address: <span>https://monica.test/magic-link/abc?signature=1&amp;expires=2</span></p>',
        )->execute();

        $this->assertStringContainsString('Sign in', $emailSent->body);
        $this->assertStringContainsString('Copy this address:', $emailSent->body);
        $this->assertStringNotContainsString('http', $emailSent->body);
        $this->assertStringNotContainsString('magic-link', $emailSent->body);
    }

    #[Test]
    public function it_keeps_no_address_from_a_real_sign_in_email(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $notification = new MagicLinkCreated(
            magicLink: new MagicLink(['expires_at' => now()->addMinutes(5)]),
            token: 'beets-bears-battlestar',
            minutes: 5,
        );

        $emailSent = new CreateEmailSent(
            account: $account,
            user: $user,
            uuid: null,
            emailType: EmailType::MagicLinkCreated->value,
            emailAddress: $user->email,
            subject: 'Your sign-in link',
            body: (string) $notification->toMail($user)->render(),
        )->execute();

        $this->assertStringContainsString('Click the button below to sign in.', $emailSent->body);
        $this->assertStringNotContainsString('http', $emailSent->body);
        $this->assertStringNotContainsString('beets-bears-battlestar', $emailSent->body);
    }
}
