<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasskeyRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyRemovedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $michael = User::factory()->create();

        $message = new PasskeyRemoved(name: 'Yubikey in a drawer')->toMail($michael);

        $this->assertStringContainsString('Yubikey in a drawer', (string) $message->render());
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $notification = new PasskeyRemoved(name: 'Yubikey in a drawer');

        $this->assertEquals(['mail'], $notification->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_passkey_that_was_removed(): void
    {
        $notification = new PasskeyRemoved(name: 'Yubikey in a drawer');

        $this->assertEquals(EmailType::PasskeyRemoved, $notification->emailType());
    }
}
