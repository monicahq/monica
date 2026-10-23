<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateSecondFactorOptions;
use App\Actions\VerifySecondFactorKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Support\WebAuthn;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\WebAuthn\SoftwareAuthenticator;
use Tests\TestCase;

class VerifySecondFactorKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_a_security_key_that_does_not_check_its_owner(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $key = $this->registerSecurityKey($michael, $authenticator);
        $options = new GenerateSecondFactorOptions(user: $michael)->execute();

        $verified = new VerifySecondFactorKey(
            user: $michael,
            credential: $authenticator->answer($options->challenge),
            options: WebAuthn::toJson($options),
        )->execute();

        $this->assertTrue($verified->is($key));
        $this->assertTrue($key->refresh()->hasBeenUsed());
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SecurityKeyUsed
            && $job->parameters === ['name' => 'YubiKey 5 NFC']);
    }

    #[Test]
    public function it_accepts_a_passkey_without_treating_it_as_a_passkey_sign_in(): void
    {
        Queue::fake();
        Event::fake([PasskeyVerified::class]);

        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $passkey = Passkey::factory()->create([
            'user_id' => $michael->id,
            'credential_id' => $authenticator->credentialId(),
            'credential' => $authenticator->credentialRecord(),
        ]);
        $options = new GenerateSecondFactorOptions(user: $michael)->execute();

        $verified = new VerifySecondFactorKey(
            user: $michael,
            credential: $authenticator->answer($options->challenge, verifiesItsOwner: true),
            options: WebAuthn::toJson($options),
        )->execute();

        $this->assertTrue($verified->is($passkey));
        Event::assertNotDispatched(PasskeyVerified::class);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyConfirmed);
    }

    #[Test]
    public function it_refuses_a_key_belonging_to_somebody_else(): void
    {
        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $this->registerSecurityKey(User::factory()->create(), $authenticator);
        $options = new GenerateSecondFactorOptions(user: $michael)->execute();

        $this->expectException(ValidationException::class);

        new VerifySecondFactorKey(
            user: $michael,
            credential: $authenticator->answer($options->challenge),
            options: WebAuthn::toJson($options),
        )->execute();
    }

    #[Test]
    public function it_refuses_an_answer_to_another_challenge(): void
    {
        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $key = $this->registerSecurityKey($michael, $authenticator);
        $options = new GenerateSecondFactorOptions(user: $michael)->execute();

        try {
            new VerifySecondFactorKey(
                user: $michael,
                credential: $authenticator->answer(random_bytes(32)),
                options: WebAuthn::toJson($options),
            )->execute();

            $this->fail('An answer to another challenge was accepted.');
        } catch (ValidationException) {
            $this->assertFalse($key->refresh()->hasBeenUsed());
        }
    }

    #[Test]
    public function it_refuses_an_answer_replayed_with_a_counter_it_has_already_seen(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        $this->registerSecurityKey($michael, $authenticator);

        $first = new GenerateSecondFactorOptions(user: $michael)->execute();
        new VerifySecondFactorKey(
            user: $michael,
            credential: $authenticator->answer($first->challenge, counter: 5),
            options: WebAuthn::toJson($first),
        )->execute();

        $second = new GenerateSecondFactorOptions(user: $michael)->execute();

        $this->expectException(ValidationException::class);

        new VerifySecondFactorKey(
            user: $michael,
            credential: $authenticator->answer($second->challenge, counter: 5),
            options: WebAuthn::toJson($second),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_deactivated_person(): void
    {
        $dwight = User::factory()->inactive()->create();
        $authenticator = new SoftwareAuthenticator;
        $this->registerSecurityKey($dwight, $authenticator);
        $options = new GenerateSecondFactorOptions(user: $dwight)->execute();

        $this->expectException(ValidationException::class);

        new VerifySecondFactorKey(
            user: $dwight,
            credential: $authenticator->answer($options->challenge),
            options: WebAuthn::toJson($options),
        )->execute();
    }

    private function registerSecurityKey(User $user, SoftwareAuthenticator $authenticator): SecurityKey
    {
        return SecurityKey::factory()->for($user)->create([
            'credential_id' => $authenticator->credentialId(),
            'credential' => $authenticator->credentialRecord(),
        ]);
    }
}
