<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewLoginDetectedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_provider_after_a_provider_sign_in(): void
    {
        config(['app.name' => 'Monica']);

        $jim = User::factory()->create();

        $message = new NewLoginDetected(
            ip: '10.0.0.1',
            method: SignInMethodEnum::Provider,
            provider: SsoProviderEnum::GitHub,
        )->toMail($jim);

        $this->assertStringContainsString('secure your GitHub account', (string) $message->panel);
    }

    #[Test]
    public function it_names_where_the_sign_in_came_from(): void
    {
        $michael = User::factory()->create();

        $message = new NewLoginDetected(ip: '10.0.0.1', method: SignInMethodEnum::EmailLink)->toMail($michael);

        $this->assertStringContainsString('10.0.0.1', (string) $message->render());
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new NewLoginDetected(ip: '10.0.0.1', method: SignInMethodEnum::EmailLink)->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_new_sign_in(): void
    {
        $this->assertEquals(EmailType::NewLogin, new NewLoginDetected(ip: '10.0.0.1', method: SignInMethodEnum::EmailLink)->emailType());
    }
}
