<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
     * A credential shaped the way the browser sends one. The signature is
     * nonsense, which is the point: nothing here should ever reach the code that
     * would check it.
     *
     * @return array<string, mixed>
     */
    private function assertion(string $credentialId): array
    {
        return [
            'id' => $credentialId,
            'rawId' => $credentialId,
            'type' => 'public-key',
            'response' => [
                'authenticatorData' => 'not-a-real-authenticator-data',
                'clientDataJSON' => 'not-a-real-client-data',
                'signature' => 'not-a-real-signature',
                'userHandle' => null,
            ],
        ];
    }

    private function registerPasskeyFor(User $user, string $credentialId): WebauthnKey
    {
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id]);

        DB::table('webauthn_keys')->where('id', $passkey->id)->update(['credentialId' => $credentialId]);

        return $passkey->refresh();
    }

    #[Test]
    public function it_refuses_a_credential_that_was_never_registered_here(): void
    {
        $response = $this->postJson(route('webauthn.auth'), $this->assertion('nobody-owns-this'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_a_deactivated_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->registerPasskeyFor($user, 'a-deactivated-user-credential');

        $response = $this->postJson(route('webauthn.auth'), $this->assertion('a-deactivated-user-credential'));

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

        $response = $this->postJson(route('webauthn.auth'), $this->assertion('a-deleted-user-credential'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_a_single_sign_on_user(): void
    {
        $user = User::factory()->singleSignOn()->create();
        $this->registerPasskeyFor($user, 'an-sso-user-credential');

        $response = $this->postJson(route('webauthn.auth'), $this->assertion('an-sso-user-credential'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.passkey.0', self::MESSAGE);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_further_attempts_once_one_credential_has_been_hammered(): void
    {
        $assertion = $this->assertion('a-credential-being-guessed');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('webauthn.auth'), $assertion)->assertStatus(422);
        }

        $this->postJson(route('webauthn.auth'), $assertion)->assertStatus(429);
    }

    #[Test]
    public function one_credential_being_hammered_does_not_lock_out_another(): void
    {
        $hammered = $this->assertion('a-credential-being-guessed');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->postJson(route('webauthn.auth'), $hammered);
        }

        $this->postJson(route('webauthn.auth'), $this->assertion('somebody-elses-credential'))
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
        $this->postJson(route('webauthn.auth'), [])->assertStatus(422);
        $this->assertGuest();

        $this->postJson(route('webauthn.auth'), [
            'id' => 'a-credential',
            'type' => 'public-key',
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
            ->map(fn (string $id): array => $this->postJson(route('webauthn.auth'), $this->assertion($id))->json())
            ->unique()
            ->all();

        $this->assertCount(1, $bodies);
    }
}
