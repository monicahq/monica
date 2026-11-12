<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SecurityKeyRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityKeyRemovedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_key_and_says_what_to_do_if_they_did_not_remove_it(): void
    {
        $michael = User::factory()->create();

        $message = new SecurityKeyRemoved(name: 'Dundie key')->toMail($michael);

        $this->assertStringContainsString('Dundie key', (string) $message->render());
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_security_key_that_was_removed(): void
    {
        $this->assertEquals(EmailType::SecurityKeyRemoved, new SecurityKeyRemoved(name: 'Dundie key')->emailType());
    }
}
