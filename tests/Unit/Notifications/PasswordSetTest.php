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
