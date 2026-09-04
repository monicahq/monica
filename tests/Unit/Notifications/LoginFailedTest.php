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
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Monica']);

        $michael = User::factory()->create();

        $message = new LoginFailed()->toMail($michael);

        $this->assertEquals('Failed sign-in attempt on your Monica account', $message->subject);
    }

    #[Test]
    public function it_tells_the_reader_what_to_do_about_it(): void
    {
        $michael = User::factory()->create();

        $message = new LoginFailed()->toMail($michael);

        $this->assertEquals(
            'If it was not you, change your password now. Somebody knows your email address and is guessing.',
            $message->panel,
        );
    }

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
