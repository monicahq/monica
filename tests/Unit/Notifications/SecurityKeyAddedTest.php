<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SecurityKeyAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityKeyAddedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_key_and_says_what_to_do_if_they_did_not_add_it(): void
    {
        $michael = User::factory()->create();

        $message = new SecurityKeyAdded(name: 'Dundie key')->toMail($michael);

        $this->assertEquals('A security key was added to your '.config('app.name').' account', $message->subject);
        $this->assertStringContainsString('Dundie key', (string) $message->render());
        $this->assertStringContainsString('If you did not add it', (string) $message->panel);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_security_key_that_was_added(): void
    {
        $this->assertEquals(EmailType::SecurityKeyAdded, new SecurityKeyAdded(name: 'Dundie key')->emailType());
    }
}
