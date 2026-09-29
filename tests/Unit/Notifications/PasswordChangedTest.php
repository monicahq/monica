<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordChangedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        config(['app.name' => 'Monica']);

        $angela = User::factory()->create();

        $message = new PasswordChanged()->toMail($angela);

        $this->assertEquals('Your Monica password was changed', $message->subject);
    }

    #[Test]
    public function it_says_the_previous_password_no_longer_works(): void
    {
        $angela = User::factory()->create();

        $message = new PasswordChanged()->toMail($angela);

        $this->assertStringContainsString('The previous one no longer works', (string) $message->render());
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_change_it(): void
    {
        $angela = User::factory()->create();

        $message = new PasswordChanged()->toMail($angela);

        $this->assertStringContainsString('If you did not change it', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $angela = User::factory()->create();

        $this->assertEquals(['mail'], new PasswordChanged()->via($angela));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_password_that_was_changed(): void
    {
        $this->assertEquals(EmailType::PasswordChanged, new PasswordChanged()->emailType());
    }
}
