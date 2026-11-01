<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordConfirmationRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordConfirmationRequestedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Monica']);
        $dwight = User::factory()->create();

        $message = new PasswordConfirmationRequested()->toMail($dwight);

        $this->assertEquals('Confirm it is you on Monica', $message->subject);
    }

    #[Test]
    public function it_carries_a_signed_link_naming_the_reader(): void
    {
        $dwight = User::factory()->create();

        $message = new PasswordConfirmationRequested()->toMail($dwight);

        $this->assertStringContainsString("/confirm-password/link/{$dwight->id}?expires=", $message->actionUrl);
        $this->assertStringContainsString('signature=', $message->actionUrl);
    }

    #[Test]
    public function it_says_how_long_the_link_works_and_what_to_do_if_nobody_asked(): void
    {
        config(['monica.magic_link_duration_minutes' => 5]);
        $dwight = User::factory()->create();

        $message = new PasswordConfirmationRequested()->toMail($dwight);

        $this->assertStringContainsString('The link works only for the next 5 minutes.', (string) $message->render());
        $this->assertStringContainsString('If you did not ask for this link, do not open it.', $message->panel);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_confirmation_request(): void
    {
        $this->assertEquals(EmailType::PasswordConfirmationRequested, new PasswordConfirmationRequested()->emailType());
    }
}
