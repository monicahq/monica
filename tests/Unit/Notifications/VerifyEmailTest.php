<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_carries_the_link_to_confirm_the_address(): void
    {
        $michael = User::factory()->unverified()->create();

        $message = new VerifyEmail()->toMail($michael);

        $this->assertStringContainsString(
            '/verify-email/'.$michael->id.'/'.sha1((string) $michael->email),
            $message->actionUrl,
        );
        $this->assertStringContainsString(
            '/verify-email/'.$michael->id.'/'.sha1((string) $michael->email),
            (string) $message->render(),
        );
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->unverified()->create();

        $this->assertEquals(['mail'], new VerifyEmail()->via($michael));
    }

    #[Test]
    public function it_is_written_in_the_language_the_reader_chose(): void
    {
        $michael = User::factory()->unverified()->create(['locale' => 'fr_FR']);

        $michael->notify(new VerifyEmail);

        $body = Mail::mailer()->getSymfonyTransport()->messages()[0]->getOriginalMessage()->getTextBody();

        $this->assertStringContainsString('Confirmez votre adresse e-mail', $body);
        $this->assertStringContainsString('Merci,', $body);
        $this->assertStringContainsString('Tous droits réservés.', $body);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_an_address_confirmation(): void
    {
        $this->assertEquals(EmailType::EmailVerification, new VerifyEmail()->emailType());
    }
}
