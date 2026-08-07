<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmailSent;
use App\Enums\EmailType;
use App\Models\Company;
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

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $emailSent = new CreateEmailSent(
            company: $company,
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
            'company_id' => $company->id,
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
        $company = Company::factory()->create();

        $emailSent = new CreateEmailSent(
            company: $company,
            user: null,
            uuid: null,
            emailType: EmailType::MagicLinkCreated->value,
            emailAddress: 'jan.levinson@dundermifflin.com',
            subject: 'Your magic link',
            body: 'Here is your link.',
        )->execute();

        $this->assertDatabaseHas('emails_sent', [
            'id' => $emailSent->id,
            'company_id' => $company->id,
            'user_id' => null,
            'uuid' => null,
        ]);
    }
}
