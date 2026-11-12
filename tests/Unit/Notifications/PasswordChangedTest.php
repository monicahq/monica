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
