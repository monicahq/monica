<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\SsoProviderEnum;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('home.index'))
            ->assertCookie(Auth::guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_asks_for_the_second_factor_before_signing_anybody_in(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('github');

        User::factory()->twoFactor()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();
        $this->mockUser('github');

        $this->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('auth.twoFactor.new'))
            ->assertSessionHas('twoFactor.signInMethod', 'provider')
            ->assertSessionHas('twoFactor.provider', 'github')
            ->assertSessionHas('twoFactor.remember', true);

        $this->assertGuest();
        Notification::assertNothingSent();
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
    public function it_sends_the_visitor_back_with_an_explanation_when_the_provider_cannot_be_reached(): void
    {
        $this->offer('openidconnect');
        config(['services.openidconnect.base_url' => 'https://id.dundermifflin.com']);

        Socialite::shouldReceive('driver->redirect')->andThrow(new \RuntimeException('unreachable'));

        $this->get(route('auth.sso.redirect', ['provider' => 'openidconnect']))
            ->assertRedirect(route('auth.login.new'))
            ->assertSessionHasErrors(['email' => 'That did not work. Try again, or sign in another way.']);
    }

    #[Test]
    public function it_creates_an_account_through_the_generic_provider(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offer('openidconnect');
        config(['services.openidconnect.base_url' => 'https://id.dundermifflin.com']);

        $this->mockUser('openidconnect');

        $this->get(route('auth.sso.callback', ['provider' => 'openidconnect']))
            ->assertRedirect(route('home.index'));

        $this->assertDatabaseHas('provider_connections', ['provider' => 'openidconnect', 'sso_id' => '108120124']);
    }

    #[Test]
    public function it_is_not_found_when_the_instance_configured_no_provider(): void
    {
        config(['monica.sso_installed' => ['github', 'google', 'facebook', 'entra']]);

        $this->get(route('auth.sso.redirect', ['provider' => 'github']))->assertNotFound();
        $this->get(route('auth.sso.callback', ['provider' => 'github']))->assertNotFound();
    }

    #[Test]
    public function it_is_not_found_when_the_configured_provider_has_no_driver_installed(): void
    {
        $this->credentials('entra');
        config(['monica.sso_installed' => ['github', 'google', 'facebook']]);

        $this->get(route('auth.sso.redirect', ['provider' => 'entra']))->assertNotFound();
        $this->get(route('auth.sso.callback', ['provider' => 'entra']))->assertNotFound();
    }

    #[Test]
    public function it_is_not_found_for_a_provider_the_product_does_not_support(): void
    {
        $this->offer('github');

        $this->get('/sso/myspace/redirect')->assertNotFound();
        $this->get('/sso/myspace/callback')->assertNotFound();
    }

    #[Test]
    public function it_relays_what_apple_posts_to_the_callback_as_a_visit_of_its_own(): void
    {
        $this->offerApple();

        $response = $this->post(route('auth.sso.relay', ['provider' => 'apple']), [
            'code' => 'c0de',
            'state' => 'st4te',
            'user' => '{"name":{"firstName":"Michael","lastName":"Scott"},"email":"michael.scott@dundermifflin.com"}',
        ]);

        $response->assertStatus(303);
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith(route('auth.sso.callback', ['provider' => 'apple']), $location);
        $this->assertSame('c0de', $query['code']);
        $this->assertSame('st4te', $query['state']);
        $this->assertStringNotContainsString('Michael', $location);
        $this->assertStringNotContainsString('dundermifflin', $location);
    }

    #[Test]
    public function it_relays_what_apple_posts_without_starting_a_session(): void
    {
        $this->offerApple();

        $route = app(Router::class)->getRoutes()->getByName('auth.sso.relay');

        $this->assertNotContains(StartSession::class, app(Router::class)->gatherRouteMiddleware($route));
        $this->post(route('auth.sso.relay', ['provider' => 'apple']), ['code' => 'c0de', 'state' => 'st4te'])
            ->assertCookieMissing(config('session.cookie'));
    }

    #[Test]
    public function it_hands_apple_back_the_name_it_relayed(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offerApple();
        $name = '{"name":{"firstName":"Michael","lastName":"Scott"}}';

        $this->mockUser('apple', expectedName: $name);

        $location = (string) $this->post(route('auth.sso.relay', ['provider' => 'apple']), ['code' => 'c0de', 'state' => 'st4te', 'user' => $name])
            ->headers->get('Location');

        $this->get($location)->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_drops_a_name_encrypted_for_anything_but_the_relay(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offerApple();

        $this->mockUser('apple', expectedName: null);

        $this->get(route('auth.sso.callback', ['provider' => 'apple', 'code' => 'c0de', 'user' => Crypt::encryptString('{"name":{"firstName":"Todd","lastName":"Packer"}}')]))
            ->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_relays_only_the_fields_apple_sends_as_text(): void
    {
        $this->offerApple();

        $location = (string) $this->post(route('auth.sso.relay', ['provider' => 'apple']), ['code' => ['c0de'], 'state' => 'st4te'])
            ->assertStatus(303)
            ->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayNotHasKey('code', $query);
        $this->assertSame('st4te', $query['state']);
    }

    #[Test]
    public function it_drops_a_name_it_did_not_relay_itself(): void
    {
        Queue::fake();
        Notification::fake();
        $this->offerApple();

        $this->mockUser('apple', expectedName: null);

        $this->get(route('auth.sso.callback', ['provider' => 'apple', 'code' => 'c0de', 'user' => '{"name":{"firstName":"Todd","lastName":"Packer"}}']))
            ->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_is_not_found_on_apple_when_its_package_is_absent(): void
    {
        $this->offerApple();
        config(['monica.sso_installed' => array_values(array_diff(SsoProviderEnum::values(), ['apple']))]);

        $this->get(route('auth.sso.redirect', ['provider' => 'apple']))->assertNotFound();
        $this->post(route('auth.sso.relay', ['provider' => 'apple']), ['code' => 'c0de'])->assertNotFound();
    }

    #[Test]
    public function it_relays_nothing_for_a_provider_that_answers_with_a_visit(): void
    {
        $this->offer('github');

        $this->post('/sso/github/callback', ['code' => 'c0de'])->assertStatus(405);
    }

    private function offerApple(): void
    {
        config([
            'monica.sso_installed' => SsoProviderEnum::values(),
            'services.apple.client_id' => 'com.dundermifflin.monica',
            'services.apple.team_id' => 'DUNDER1234',
            'services.apple.key_id' => 'SCRANTON12',
            'services.apple.private_key' => '/etc/monica/AuthKey_SCRANTON12.p8',
            'services.apple.redirect' => 'https://monica.test/sso/apple/callback',
        ]);
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

    private function mockUser(string $provider, ?string $expectedName = null): void
    {
        $providerUser = new ProviderUser()->map([
            'id' => '108120124',
            'email' => 'michael.scott@dundermifflin.com',
            'name' => 'Michael Scott',
            'nickname' => 'mscott',
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturnUsing(function () use ($expectedName, $providerUser): ProviderUser {
            $this->assertSame($expectedName, request()->input('user'));

            return $providerUser;
        });
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }
}
