<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasskeyAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyAddedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyAdded(name: 'MacBook Pro')->toMail($michael);

        $this->assertEquals(
            'A passkey was added to your '.config('app.name').' account',
            $message->subject,
        );
    }

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyAdded(name: 'MacBook Pro')->toMail($michael);

        $this->assertStringContainsString('MacBook Pro', (string) $message->render());
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_add_it(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyAdded(name: 'MacBook Pro')->toMail($michael);

        $this->assertStringContainsString('If you did not add it', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new PasskeyAdded(name: 'MacBook Pro')->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_passkey_that_was_added(): void
    {
        $this->assertEquals(EmailType::PasskeyAdded, new PasskeyAdded(name: 'MacBook Pro')->emailType());
    }
}
