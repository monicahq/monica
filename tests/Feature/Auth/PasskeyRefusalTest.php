<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyRefusalTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'These credentials do not match our records.';

    /** @return array<string, mixed> */
    private function assertion(string $label): array
    {
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'origin' => config('app.url'),
        ], JSON_THROW_ON_ERROR);

        return [
            'credential' => [
                'id' => $this->credentialId($label),
                'rawId' => $this->credentialId($label),
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                    'authenticatorData' => Base64UrlSafe::encodeUnpadded(
                        hash('sha256', 'localhost', true).chr(0x05).pack('N', 1)
                    ),
                    'signature' => Base64UrlSafe::encodeUnpadded('not-a-real-signature'),
                ],
            ],
            'remember' => false,
        ];
    }

    private function credentialId(string $label): string
    {
        return Base64UrlSafe::encodeUnpadded($label);
    }

    private function withChallenge(): self
    {
        $options = app(GenerateVerificationOptions::class)();

        return $this->withSession(['passkey.verification_options' => WebAuthn::toJson($options)]);
    }

    /** @param  array<string, mixed>  $assertion */
    private function attemptPasskeySignIn(array $assertion): TestResponse
    {
        return $this->withChallenge()->postJson(route('passkey.login'), $assertion);
    }

    private function registerPasskeyFor(User $user, string $label): Passkey
    {
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        DB::table('passkeys')->where('id', $passkey->id)->update(['credential_id' => $this->credentialId($label)]);

        return $passkey->refresh();
    }

    #[Test]
    public function it_refuses_a_credential_that_was_never_registered_here(): void
    {
        $response = $this->attemptPasskeySignIn($this->assertion('nobody-owns-this'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_a_soft_deleted_user(): void
    {
        $user = User::factory()->create();
        $this->registerPasskeyFor($user, 'a-deleted-user-credential');
        $user->delete();

        $response = $this->attemptPasskeySignIn($this->assertion('a-deleted-user-credential'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function a_refused_sign_in_leaves_nothing_behind(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create(['is_active' => false]);
        $passkey = $this->registerPasskeyFor($user, 'a-deactivated-user-credential');

        $this->attemptPasskeySignIn($this->assertion('a-deactivated-user-credential'))->assertStatus(422);

        $this->assertNull($passkey->refresh()->last_used_at);
        Queue::assertNotPushed(LogUserAction::class);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_further_attempts_once_one_credential_has_been_hammered(): void
    {
        $assertion = $this->assertion('a-credential-being-guessed');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->attemptPasskeySignIn($assertion)->assertStatus(422);
        }

        $this->attemptPasskeySignIn($assertion)->assertStatus(429);
    }

    #[Test]
    public function it_signs_nobody_in_from_an_assertion_that_never_arrived(): void
    {
        $this->attemptPasskeySignIn([])->assertStatus(422);
        $this->assertGuest();

        $this->attemptPasskeySignIn([
            'credential' => [
                'id' => 'a-credential',
                'type' => 'public-key',
            ],
        ])->assertStatus(422);
        $this->assertGuest();
    }
}
