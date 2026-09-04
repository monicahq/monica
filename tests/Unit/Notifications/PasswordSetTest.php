<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordSetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordSet()->toMail($michael);

        $this->assertEquals(
            'A password was set on your '.config('app.name').' account',
            $message->subject,
        );
    }

    #[Test]
    public function it_says_a_passkey_still_works_too(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordSet()->toMail($michael);

        $this->assertStringContainsString(
            'as well as with any passkey you hold',
            (string) $message->render(),
        );
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_set_it(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordSet()->toMail($michael);

        $this->assertStringContainsString('If you did not set it', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new PasswordSet()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_password_that_was_set(): void
    {
        $this->assertEquals(EmailType::PasswordSet, new PasswordSet()->emailType());
    }
}
