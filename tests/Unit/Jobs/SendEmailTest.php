<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Resend\Email;
use Resend\Service\Email as EmailService;
use Tests\Fixtures\Mail\NewLoginDetected;
use Tests\TestCase;

class SendEmailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_email_the_traditional_way(): void
    {
        Config::set('app.use_resend', false);
        Config::set('mail.from.address', 'noreply@officelife.test');
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new SendEmail(
            mailable: new NewLoginDetected,
            company: $company,
            emailType: EmailType::NewLogin,
            user: $user,
        )->handle();

        // Sent, not queued. SendEmail is itself the queued unit, so the mailable must
        // go out inside this job: if it queued itself instead, the row recorded below
        // would be written before the email had actually left.
        Mail::assertSent(
            NewLoginDetected::class,
            fn (NewLoginDetected $mail): bool => $mail->hasTo('michael.scott@dundermifflin.com'),
        );

        $emailSent = EmailSent::query()->latest()->first();

        $this->assertEquals($company->id, $emailSent->company_id);
        $this->assertEquals($user->id, $emailSent->user_id);
        $this->assertEquals(EmailType::NewLogin->value, $emailSent->email_type);
        $this->assertEquals('michael.scott@dundermifflin.com', $emailSent->email_address);
        $this->assertEquals('A new login on your OfficeLife account', $emailSent->subject);
        $this->assertNull($emailSent->uuid);
        $this->assertNotNull($emailSent->sent_at);
    }

    #[Test]
    public function it_sends_the_email_with_resend(): void
    {
        Config::set('app.use_resend', true);
        Config::set('mail.from.address', 'noreply@officelife.test');

        $emailsMock = Mockery::mock(EmailService::class);
        $emailsMock
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::on(
                fn ($arguments): bool => (
                    $arguments['from'] === 'noreply@officelife.test'
                    && $arguments['to'] === ['michael.scott@dundermifflin.com']
                    && $arguments['subject'] === 'A new login on your OfficeLife account'
                    && is_string($arguments['html'])
                    && mb_strlen($arguments['html']) > 0
                ),
            ))
            ->andReturn(Email::from(['id' => 'resend-uuid-12345']));

        $resendMock = Mockery::mock();
        $resendMock
            ->shouldReceive('emails')
            ->once()
            ->andReturn($emailsMock);

        app()->instance('resend', $resendMock);

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new SendEmail(
            mailable: new NewLoginDetected,
            company: $company,
            emailType: EmailType::NewLogin,
            user: $user,
        )->handle();

        $emailSent = EmailSent::query()->latest()->first();

        $this->assertEquals('resend-uuid-12345', $emailSent->uuid);
        $this->assertEquals('michael.scott@dundermifflin.com', $emailSent->email_address);
    }

    #[Test]
    public function it_sends_the_email_to_an_address_that_has_no_user(): void
    {
        Config::set('app.use_resend', false);
        Mail::fake();

        $company = Company::factory()->create();

        new SendEmail(
            mailable: new NewLoginDetected,
            company: $company,
            emailType: EmailType::MagicLinkCreated,
            emailAddress: 'jan.levinson@dundermifflin.com',
        )->handle();

        Mail::assertSent(
            NewLoginDetected::class,
            fn (NewLoginDetected $mail): bool => $mail->hasTo('jan.levinson@dundermifflin.com'),
        );

        $emailSent = EmailSent::query()->latest()->first();

        $this->assertEquals($company->id, $emailSent->company_id);
        $this->assertNull($emailSent->user_id);
        $this->assertEquals('jan.levinson@dundermifflin.com', $emailSent->email_address);
    }

    #[Test]
    public function it_prefers_the_given_address_over_the_email_of_the_user(): void
    {
        Config::set('app.use_resend', false);
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new SendEmail(
            mailable: new NewLoginDetected,
            company: $company,
            emailType: EmailType::NewLogin,
            user: $user,
            emailAddress: 'michael.scarn@dundermifflin.com',
        )->handle();

        $emailSent = EmailSent::query()->latest()->first();

        $this->assertEquals($user->id, $emailSent->user_id);
        $this->assertEquals('michael.scarn@dundermifflin.com', $emailSent->email_address);
    }

    #[Test]
    public function it_throws_when_there_is_neither_a_user_nor_an_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Config::set('app.use_resend', false);
        Mail::fake();

        $company = Company::factory()->create();

        new SendEmail(
            mailable: new NewLoginDetected,
            company: $company,
            emailType: EmailType::NewLogin,
        )->handle();
    }
}
