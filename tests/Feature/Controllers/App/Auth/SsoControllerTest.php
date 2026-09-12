<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\SsoProviderEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_visitor_to_the_provider(): void
    {
        $this->offer('github');

        $this->mockRedirect('github');

        $this->get(route('auth.sso.redirect', ['provider' => 'github']))
            ->assertRedirect('https://github.com/login/oauth/authorize');
    }

    #[Test]
    public function it_signs_in_the_user_the_provider_names(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('github');

        $user = User::factory()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('home.index'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_creates_an_account_for_somebody_it_has_never_seen(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('github');

        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('home.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'michael.scott@dundermifflin.com',
            'sso_provider' => 'github',
            'sso_id' => '108120124',
        ]);
        $this->assertAuthenticated();
    }

    #[Test]
    public function it_asks_for_the_second_factor_before_signing_anybody_in(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('github');

        User::factory()->twoFactor()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('auth.twoFactor.new'));

        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_deactivated_user(): void
    {
        Queue::fake();
        $this->offer('github');

        User::factory()->create([
            'is_active' => false,
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('auth.login.new'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_an_address_that_belongs_to_somebody_signing_in_another_way(): void
    {
        Queue::fake();
        $this->offer('github');

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('auth.login.new'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function it_sends_a_cancelled_visitor_back_with_an_explanation(): void
    {
        Queue::fake();
        $this->offer('github');

        Socialite::shouldReceive('driver->user')->andThrow(new \RuntimeException('cancelled'));

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('auth.login.new'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertEquals(0, User::query()->count());
    }

    #[Test]
    public function it_is_not_found_when_the_instance_configured_no_provider(): void
    {
        config(['monica.sso_installed' => ['github', 'google', 'facebook', 'microsoft']]);

        $this->get(route('auth.sso.redirect', ['provider' => 'github']))->assertNotFound();
        $this->get(route('auth.sso.callback', ['provider' => 'github']))->assertNotFound();
    }

    #[Test]
    public function it_is_not_found_when_the_configured_provider_has_no_package(): void
    {
        $this->credentials('microsoft');
        config(['monica.sso_installed' => ['github', 'google', 'facebook']]);

        $this->get(route('auth.sso.redirect', ['provider' => 'microsoft']))->assertNotFound();
        $this->get(route('auth.sso.callback', ['provider' => 'microsoft']))->assertNotFound();
    }

    #[Test]
    public function it_is_not_found_for_a_provider_the_product_does_not_support(): void
    {
        $this->offer('github');

        $this->get('/sso/myspace/redirect')->assertNotFound();
        $this->get('/sso/myspace/callback')->assertNotFound();
    }

    #[Test]
    public function it_offers_only_the_providers_the_instance_made_available(): void
    {
        $this->offer('github');
        $this->offer('google');

        $response = $this->get(route('auth.login.new'));

        $response->assertSee('Continue with GitHub');
        $response->assertSee('Continue with Google');
        $response->assertDontSee('Continue with Microsoft');
        $response->assertDontSee('Continue with Facebook');
    }

    #[Test]
    public function it_shows_the_sign_in_screen_unchanged_when_nothing_is_available(): void
    {
        config(['monica.sso_installed' => []]);

        $response = $this->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertDontSee('Continue with');
    }

    #[Test]
    public function it_keeps_nothing_the_provider_issued(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('github');

        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']));

        $user = User::query()->firstOrFail();

        $this->assertNull($user->password);
        $this->assertEquals('108120124', $user->sso_id);
        $this->assertEmpty(array_intersect(
            ['access_token', 'refresh_token', 'token'],
            array_keys($user->getAttributes()),
        ));
    }

    private function offer(string $provider): void
    {
        $this->credentials($provider);
        config(['monica.sso_installed' => SsoProviderEnum::values()]);
    }

    private function credentials(string $provider): void
    {
        config([
            "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
            "services.{$provider}.client_secret" => 'that-is-what-she-said',
            "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
        ]);
    }

    private function mockRedirect(string $provider): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('redirect')->andReturn(redirect('https://github.com/login/oauth/authorize'));
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    private function mockUser(string $provider): void
    {
        $providerUser = new ProviderUser()->map([
            'id' => '108120124',
            'email' => 'michael.scott@dundermifflin.com',
            'name' => 'Michael Scott',
            'nickname' => 'mscott',
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($providerUser);
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }
}
