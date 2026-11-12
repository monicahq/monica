<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\LoginFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginFailedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new LoginFailed()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_failed_sign_in(): void
    {
        $this->assertEquals(EmailType::LoginFailed, new LoginFailed()->emailType());
    }
}
