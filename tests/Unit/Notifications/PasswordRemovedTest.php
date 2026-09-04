<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordRemovedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordRemoved()->toMail($michael);

        $this->assertEquals(
            'Your '.config('app.name').' password was removed',
            $message->subject,
        );
    }

    #[Test]
    public function it_names_what_signs_them_in_now(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordRemoved()->toMail($michael);

        $this->assertStringContainsString(
            'Your passkeys, and a link sent to this address',
            (string) $message->render(),
        );
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_remove_it(): void
    {
        $michael = User::factory()->create();

        $message = new PasswordRemoved()->toMail($michael);

        $this->assertStringContainsString('If you did not remove it', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new PasswordRemoved()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_password_that_was_removed(): void
    {
        $this->assertEquals(EmailType::PasswordRemoved, new PasswordRemoved()->emailType());
    }
}
