<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_screen_that_pairs_an_authenticator_app(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->withConfirmedPassword()->actingAs($user)->get(route('settings.twoFactor.new'));

        $response->assertStatus(200);
        $response->assertSee('Pair your authenticator app', escape: false);
        $response->assertSee('michael.scott@dundermifflin.com', escape: false);
        $response->assertSee('<svg', escape: false);

        $this->assertNotNull($user->refresh()->two_factor_secret);
    }

    #[Test]
    public function it_sends_somebody_whose_app_is_on_back_to_the_security_screen(): void
    {
        $user = User::factory()->twoFactor(secret: 'DWIGHTSCHRUTEBEETS')->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->get(route('settings.twoFactor.new'));

        $response->assertRedirect(route('settings.security.index'));

        $user->refresh();

        $this->assertEquals('DWIGHTSCHRUTEBEETS', $user->two_factor_secret);
        $this->assertTrue($user->usesAuthenticatorApp());
    }

    #[Test]
    public function it_turns_two_factor_authentication_on(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $response->assertRedirect(route('settings.recoveryCodes.show'));
        $response->assertSessionHas('status', 'Two-factor authentication is on.');

        $this->assertTrue($user->refresh()->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_says_so_when_the_code_is_not_right(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'two_factor_secret' => new Google2FA()->generateSecretKey(),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.twoFactor.create'), [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');

        $this->assertFalse($user->refresh()->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_turns_two_factor_authentication_off(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.twoFactor.destroy'));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Two-factor authentication is off.');

        $user->refresh();

        $this->assertFalse($user->usesTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
    }

    #[Test]
    public function it_shows_the_codes_once_after_enrolment(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $this->followRedirects($response)
            ->assertStatus(200)
            ->assertSee($user->refresh()->two_factor_recovery_codes[0], escape: false)
            ->assertSee('This is the only time they are shown', escape: false);
    }

    #[Test]
    public function it_keeps_the_codes_out_of_sight_when_the_app_joins_a_security_key(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['scranton-1'],
        ]);
        SecurityKey::factory()->for($user)->create();

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->post(route('settings.twoFactor.create'), ['code' => $google2fa->getCurrentOtp($secret)]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionMissing('recoveryCodes');
    }

    #[Test]
    public function it_says_the_security_keys_still_answer_once_the_app_is_turned_off(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();
        SecurityKey::factory()->for($user)->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.twoFactor.destroy'));

        $response->assertSessionHas('status', 'The authenticator app is removed.');
        $this->assertTrue($user->refresh()->usesTwoFactorAuthentication());
    }
}
