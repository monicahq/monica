<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\SsoProviderEnum;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
    public function it_still_signs_in_a_user_who_has_also_connected_a_provider(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'sso_provider' => SsoProviderEnum::Google,
            'sso_id' => '108120124',
        ]);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function it_still_refuses_a_user_who_signed_up_through_a_provider_and_has_no_password(): void
    {
        Queue::fake();

        User::factory()->singleSignOn()->create(['email' => 'michael.scott@dundermifflin.com']);

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

        $response = $this->get($this->signedMagicLinkFor($user));

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

        $response = $this->get($this->signedMagicLinkFor($user));

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
    public function it_uses_the_framework_provider_for_the_only_provider_there_is(): void
    {
        $this->assertInstanceOf(
            EloquentUserProvider::class,
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

    private function signedMagicLinkFor(User $user): string
    {
        $link = MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        return URL::temporarySignedRoute(
            'auth.magicLink.show',
            $link->expires_at,
            [
                'token' => self::TOKEN,
            ],
        );
    }
}
