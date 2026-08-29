<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use LaravelWebauthn\Auth\EloquentWebAuthnProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The `users` provider is the only one this application has, and it now runs on
 * the `webauthn` driver, so every sign in travels through
 * `EloquentWebAuthnProvider` whether or not a passkey is involved. That provider
 * only checks a password while `webauthn.fallback` is true.
 *
 * Nothing here is about passkeys. This file exists so that a mistake in that
 * configuration is caught by a failing test naming the credential it broke,
 * rather than by nobody being able to sign in.
 */
class ProviderRegressionTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

    #[Test]
    public function it_still_signs_somebody_in_with_a_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_still_refuses_a_wrong_password(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_still_refuses_a_deactivated_user(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'is_active' => false,
        ]);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_still_refuses_a_user_who_signs_in_through_an_identity_provider(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'sso_provider' => 'google',
        ]);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_still_signs_somebody_in_through_a_magic_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_still_asks_a_magic_link_user_for_their_two_factor_code(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'two_factor_secret' => 'BBBBBBBBBBBBBBBB',
            'two_factor_confirmed_at' => now(),
        ]);

        MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('auth.twoFactor.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_still_asks_a_password_user_for_their_two_factor_code(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'two_factor_secret' => 'BBBBBBBBBBBBBBBB',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('auth.twoFactor.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_still_issues_a_remember_cookie(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->remember_token);
    }

    #[Test]
    public function it_uses_the_webauthn_driver_for_the_only_provider_there_is(): void
    {
        $this->assertInstanceOf(
            EloquentWebAuthnProvider::class,
            Auth::guard('web')->getProvider(),
        );
    }

    #[Test]
    public function it_still_resolves_a_user_from_an_email_and_checks_their_password(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $provider = Auth::guard('web')->getProvider();

        $found = $provider->retrieveByCredentials(['email' => 'michael.scott@dundermifflin.com']);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
        $this->assertTrue($provider->validateCredentials($found, ['password' => 'password']));
        $this->assertFalse($provider->validateCredentials($found, ['password' => 'thatswhatshesaid']));
    }

    #[Test]
    public function it_still_resolves_a_user_from_their_remember_token(): void
    {
        $user = User::factory()->create(['remember_token' => 'dundermifflinpaperco']);

        $found = Auth::guard('web')->getProvider()->retrieveByToken($user->id, 'dundermifflinpaperco');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_refuses_a_password_when_the_fallback_is_off(): void
    {
        config(['webauthn.fallback' => false]);

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $provider = Auth::guard('web')->getProvider();

        $this->assertFalse($provider->validateCredentials($user, ['password' => 'password']));
    }
}
