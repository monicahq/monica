<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmailSent;
use App\Enums\EmailType;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
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
}
