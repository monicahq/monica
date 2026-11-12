<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateSecurityKey;
use App\Actions\GenerateSecurityKeyOptions;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\SecurityKeyAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\WebAuthn\SoftwareAuthenticator;
use Tests\TestCase;

class CreateSecurityKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_the_first_key_and_turns_two_factor_authentication_on(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $options = new GenerateSecurityKeyOptions(user: $michael)->execute();

        $securityKey = new CreateSecurityKey(
            user: $michael,
            name: '_',
            credential: $authenticator->registration($options->challenge),
            options: WebAuthn::toJson($options),
        )->execute();

        $this->assertDatabaseHas('security_keys', [
            'id' => $securityKey->id,
            'user_id' => $michael->id,
            'credential_id' => $authenticator->credentialId(),
        ]);
        $this->assertNotSame('_', $securityKey->name);
        $this->assertTrue($michael->refresh()->usesTwoFactorAuthentication());
        $this->assertNotEmpty($michael->two_factor_recovery_codes);
        Notification::assertSentTo($michael, SecurityKeyAdded::class);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SecurityKeyAdded
            && $job->parameters === ['name' => $securityKey->name]);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorEnabled);
    }

    #[Test]
    public function it_keeps_the_name_and_the_recovery_codes_somebody_already_has(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->twoFactor()->create();
        $authenticator = new SoftwareAuthenticator;
        $options = new GenerateSecurityKeyOptions(user: $michael)->execute();

        new CreateSecurityKey(
            user: $michael,
            name: 'Dundie key',
            credential: $authenticator->registration($options->challenge),
            options: WebAuthn::toJson($options),
        )->execute();

        $this->assertDatabaseHas('security_keys', ['user_id' => $michael->id, 'name' => 'Dundie key']);
        $this->assertEquals(['scranton-1', 'scranton-2'], $michael->refresh()->two_factor_recovery_codes);
        Queue::assertNotPushed(LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorEnabled);
    }

    #[Test]
    public function it_refuses_a_key_already_added(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $first = new GenerateSecurityKeyOptions(user: $michael)->execute();
        new CreateSecurityKey(
            user: $michael,
            name: '_',
            credential: $authenticator->registration($first->challenge),
            options: WebAuthn::toJson($first),
        )->execute();

        $second = new GenerateSecurityKeyOptions(user: $michael)->execute();

        $this->expectException(ValidationException::class);

        new CreateSecurityKey(
            user: $michael,
            name: '_',
            credential: $authenticator->registration($second->challenge),
            options: WebAuthn::toJson($second),
        )->execute();
    }

    #[Test]
    public function it_refuses_an_answer_to_another_challenge_and_stores_nothing(): void
    {
        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $options = new GenerateSecurityKeyOptions(user: $michael)->execute();

        try {
            new CreateSecurityKey(
                user: $michael,
                name: '_',
                credential: $authenticator->registration(random_bytes(32)),
                options: WebAuthn::toJson($options),
            )->execute();

            $this->fail('An answer to another challenge was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('security_keys', 0);
            $this->assertFalse($michael->refresh()->usesTwoFactorAuthentication());
        }
    }
}
