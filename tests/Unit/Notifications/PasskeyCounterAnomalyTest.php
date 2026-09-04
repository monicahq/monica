<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasskeyCounterAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyCounterAnomalyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyCounterAnomaly(name: 'Michael MacBook')->toMail($michael);

        $this->assertEquals(
            'Something is wrong with a passkey on your '.config('app.name').' account',
            $message->subject,
        );
    }

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyCounterAnomaly(name: 'Michael MacBook')->toMail($michael);

        $this->assertStringContainsString('Michael MacBook', (string) $message->render());
    }

    #[Test]
    public function it_says_what_to_do_about_it(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyCounterAnomaly(name: 'Michael MacBook')->toMail($michael);

        $this->assertStringContainsString('Remove that passkey', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $notification = new PasskeyCounterAnomaly(name: 'Michael MacBook');

        $this->assertEquals(['mail'], $notification->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_passkey_that_answered_wrongly(): void
    {
        $notification = new PasskeyCounterAnomaly(name: 'Michael MacBook');

        $this->assertEquals(EmailType::PasskeyCounterAnomaly, $notification->emailType());
    }
}
