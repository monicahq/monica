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

/**
 * Who is refused a passkey sign-in, and what they are told.
 *
 * None of this needs a real credential. The stage that decides runs before the
 * signature is looked at, on purpose, so a refused user is turned away without
 * an assertion ever being verified. That is what makes these testable, and it is
 * also the behaviour worth pinning: the refusal has to come first.
 */
class PasskeyRefusalTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'These credentials do not match our records.';

    /**
     * A credential shaped the way the browser sends one, and well formed enough
     * to survive being deserialised. The signature is nonsense, which is the
     * point: nothing here should ever reach the code that would check it.
     *
     * It has to be well formed now in a way it did not before. The package
     * deserialises the credential while validating the request, so a payload of
     * plausible-looking rubbish is refused for its shape and never reaches the
     * stage under test.
     *
     * @return array<string, mixed>
     */
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

    /**
     * The credential id as it travels and as it is stored: the same base64url
     * string at both ends, because the lookup re-encodes what it decodes.
     */
    private function credentialId(string $label): string
    {
        return Base64UrlSafe::encodeUnpadded($label);
    }

    /**
     * The challenge the sign-in answers, put where the browser's first request
     * would have left it. Seeding the session directly rather than calling the
     * options route keeps these tests about the refusal: the options route is
     * throttled on the same allowance, and calling it would spend the attempts
     * the hammering tests are counting.
     */
    private function withChallenge(): self
    {
        $options = app(GenerateVerificationOptions::class)();

        return $this->withSession(['passkey.verification_options' => WebAuthn::toJson($options)]);
    }

    /**
     * @param  array<string, mixed>  $assertion
     */
    private function attempt(array $assertion): TestResponse
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
        $response = $this->attempt($this->assertion('nobody-owns-this'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_a_deactivated_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->registerPasskeyFor($user, 'a-deactivated-user-credential');

        $response = $this->attempt($this->assertion('a-deactivated-user-credential'));

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

        $response = $this->attempt($this->assertion('a-deleted-user-credential'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_a_single_sign_on_user(): void
    {
        $user = User::factory()->singleSignOn()->create();
        $this->registerPasskeyFor($user, 'an-sso-user-credential');

        $response = $this->attempt($this->assertion('an-sso-user-credential'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    /**
     * The whole reason the refusal was moved off the package's own hook. That
     * hook runs after the assertion has been verified, by which point the
     * package has stamped the passkey, persisted the counter and announced a
     * sign in. A refused person must collect none of that, so the three
     * absences are asserted rather than the refusal alone.
     */
    #[Test]
    public function a_refused_sign_in_leaves_nothing_behind(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create(['is_active' => false]);
        $passkey = $this->registerPasskeyFor($user, 'a-deactivated-user-credential');

        $this->attempt($this->assertion('a-deactivated-user-credential'))->assertStatus(422);

        $this->assertNull($passkey->refresh()->last_used_at);
        Queue::assertNotPushed(LogUserAction::class);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_further_attempts_once_one_credential_has_been_hammered(): void
    {
        $assertion = $this->assertion('a-credential-being-guessed');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->attempt($assertion)->assertStatus(422);
        }

        $this->attempt($assertion)->assertStatus(429);
    }

    #[Test]
    public function one_credential_being_hammered_does_not_lock_out_another(): void
    {
        $hammered = $this->assertion('a-credential-being-guessed');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->attempt($hammered);
        }

        $this->attempt($this->assertion('somebody-elses-credential'))
            ->assertStatus(422);
    }

    /**
     * An abandoned prompt posts nothing at all, so the only thing the server can
     * be held to is that a half or missing assertion signs nobody in. The screen
     * returning to its starting state is the component's job, and is covered by
     * the JavaScript that reads `NotAllowedError`.
     */
    #[Test]
    public function it_signs_nobody_in_from_an_assertion_that_never_arrived(): void
    {
        $this->attempt([])->assertStatus(422);
        $this->assertGuest();

        $this->attempt([
            'credential' => [
                'id' => 'a-credential',
                'type' => 'public-key',
            ],
        ])->assertStatus(422);
        $this->assertGuest();
    }

    #[Test]
    public function every_refusal_reads_exactly_the_same(): void
    {
        $deactivated = User::factory()->create(['is_active' => false]);
        $this->registerPasskeyFor($deactivated, 'deactivated');

        $sso = User::factory()->singleSignOn()->create();
        $this->registerPasskeyFor($sso, 'sso');

        $bodies = collect(['unregistered', 'deactivated', 'sso'])
            ->map(fn (string $id): array => $this->attempt($this->assertion($id))->json())
            ->unique()
            ->all();

        $this->assertCount(1, $bodies);
    }
}
