<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SignInProviderDisconnected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignInProviderDisconnectedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_provider_in_the_subject_and_the_greeting(): void
    {
        $user = User::factory()->create();

        $message = new SignInProviderDisconnected(provider: 'GitHub')->toMail($user);

        $this->assertEquals('GitHub was disconnected from your '.config('app.name').' account', $message->subject);
        $this->assertStringContainsString('GitHub no longer signs you in', (string) $message->render());
    }

    #[Test]
    public function it_says_the_account_is_untouched(): void
    {
        $user = User::factory()->create();

        $message = new SignInProviderDisconnected(provider: 'GitHub')->toMail($user);

        $this->assertStringContainsString(
            'Your account is untouched, and whatever else you use to sign in still works.',
            (string) $message->render(),
        );
        $this->assertEquals(
            'If you did not disconnect it, connect it again from your security settings straight away.',
            $message->panel,
        );
    }

    #[Test]
    public function it_is_recorded_as_its_own_kind_of_email(): void
    {
        $this->assertEquals(
            EmailType::SignInProviderDisconnected,
            new SignInProviderDisconnected(provider: 'GitHub')->emailType(),
        );
    }

    #[Test]
    public function it_goes_on_the_high_queue(): void
    {
        $this->assertEquals('high', new SignInProviderDisconnected(provider: 'GitHub')->queue);
    }

    #[Test]
    public function it_is_sent_by_email(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(['mail'], new SignInProviderDisconnected(provider: 'GitHub')->via($user));
    }
}
