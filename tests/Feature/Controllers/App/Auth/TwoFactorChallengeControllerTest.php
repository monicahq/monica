<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Models\SecurityKey;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\Fixtures\WebAuthn\SoftwareAuthenticator;
use Tests\TestCase;

class TwoFactorChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_challenge_to_somebody_whose_password_was_accepted(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->get(route('auth.twoFactor.new'));

        $response->assertStatus(200);
        $response->assertSee('One more step', escape: false);
    }

    #[Test]
    public function it_sends_somebody_back_to_the_sign_in_screen_when_no_password_was_accepted(): void
    {
        $response = $this->get(route('auth.twoFactor.new'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_signs_somebody_in_when_the_code_is_right(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->post(route('auth.twoFactor.create'), [
                'code' => $google2fa->getCurrentOtp($secret),
            ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('twoFactor.user.id'));
    }

    #[Test]
    public function it_stops_accepting_codes_after_five_wrong_ones(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['twoFactor.user.id' => $user->id])
                ->post(route('auth.twoFactor.create'), ['code' => '000000']);
        }

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->from(route('auth.twoFactor.new'))
            ->post(route('auth.twoFactor.create'), [
                'code' => $google2fa->getCurrentOtp($secret),
            ]);

        $response->assertRedirect(route('auth.twoFactor.new'));
        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    #[Test]
    public function it_warns_about_a_sign_in_without_a_password_once_the_code_is_right(): void
    {
        Notification::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        $this->withSession([
            'twoFactor.user.id' => $user->id,
            'twoFactor.signInMethod' => 'provider',
            'twoFactor.provider' => 'github',
        ])->post(route('auth.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        Notification::assertSentTo(
            $user,
            NewLoginDetected::class,
            fn (NewLoginDetected $notification): bool => $notification->method === SignInMethodEnum::Provider
                && $notification->provider === SsoProviderEnum::GitHub,
        );
    }

    #[Test]
    public function it_remembers_somebody_who_asked_to_be_remembered_before_the_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create(['email' => 'jim@dundermifflin.com']);

        $this->post(route('auth.login.create'), [
            'email' => 'jim@dundermifflin.com',
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect(route('auth.twoFactor.new'));

        $response = $this->post(route('auth.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertNull(session('twoFactor.remember'));
    }

    #[Test]
    public function it_forgets_somebody_who_did_not_ask_to_be_remembered(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create(['email' => 'jim@dundermifflin.com']);

        $this->post(route('auth.login.create'), [
            'email' => 'jim@dundermifflin.com',
            'password' => 'password',
        ])->assertRedirect(route('auth.twoFactor.new'));

        $response = $this->post(route('auth.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    #[Test]
    public function it_remembers_somebody_who_came_through_a_link_by_email(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        $response = $this->withSession([
            'twoFactor.user.id' => $user->id,
            'twoFactor.remember' => true,
            'twoFactor.signInMethod' => 'email_link',
        ])->post(route('auth.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    #[Test]
    public function it_signs_somebody_in_with_a_security_key_and_warns_them_once(): void
    {
        Notification::fake();
        URL::forceScheme('https');
        $user = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        SecurityKey::factory()->for($user)->create([
            'credential_id' => $authenticator->credentialId(),
            'credential' => $authenticator->credentialRecord(),
        ]);

        $this->withSession([
            'twoFactor.user.id' => $user->id,
            'twoFactor.signInMethod' => SignInMethodEnum::EmailLink->value,
        ]);
        $challenge = $this->getJson(route('auth.twoFactorKeyOptions.show'))->json('options.challenge');

        $response = $this->postJson(route('auth.twoFactor.create'), [
            'credential' => $authenticator->answer(Base64UrlSafe::decodeNoPadding($challenge)),
            'remember' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('redirect', route('home.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('twoFactor.user.id'));
        Notification::assertSentToTimes($user, NewLoginDetected::class, 1);
    }

    #[Test]
    public function it_keeps_somebody_on_the_challenge_when_the_key_is_refused(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;
        SecurityKey::factory()->for($user)->create([
            'credential_id' => $authenticator->credentialId(),
            'credential' => $authenticator->credentialRecord(),
        ]);

        $this->withSession(['twoFactor.user.id' => $user->id]);
        $this->getJson(route('auth.twoFactorKeyOptions.show'))->assertOk();

        $response = $this->postJson(route('auth.twoFactor.create'), [
            'credential' => $authenticator->answer(random_bytes(32)),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('credential');
        $this->assertGuest();
        $this->assertEquals($user->id, session('twoFactor.user.id'));
    }

    #[Test]
    public function it_says_it_took_too_long_when_a_key_answers_a_challenge_nobody_asked(): void
    {
        $user = User::factory()->create();
        $authenticator = new SoftwareAuthenticator;

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->postJson(route('auth.twoFactor.create'), [
                'credential' => $authenticator->answer(random_bytes(32)),
            ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('redirect', route('auth.login.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_code_that_is_not_right(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->post(route('auth.twoFactor.create'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
