<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Enums\SsoProviderEnum;
use App\Http\Controllers\App\Auth\SsoController as AuthSsoController;
use App\Models\Passkey;
use App\Models\ProviderConnection;
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
    public function it_sends_a_signed_in_user_to_the_provider_and_remembers_why(): void
    {
        $this->offer('github');
        $this->mockRedirect('github');

        $this->withConfirmedPassword()
            ->actingAs(User::factory()->create())
            ->get(route('settings.sso.redirect', ['provider' => 'github']))
            ->assertRedirect('https://github.com/login/oauth/authorize')
            ->assertSessionHas(AuthSsoController::CONNECT_INTENT, true);
    }

    #[Test]
    public function it_connects_the_provider_when_the_visitor_comes_back(): void
    {
        Queue::fake();
        $this->offer('github');
        $this->mockUser('github');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([AuthSsoController::CONNECT_INTENT => true])
            ->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github', 'sso_id' => '108120124']);
        $this->assertNotNull($user->refresh()->password);
    }

    #[Test]
    public function it_refuses_a_provider_account_that_belongs_to_somebody_else(): void
    {
        Queue::fake();
        $this->offer('github');
        $this->mockUser('github');

        User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([AuthSsoController::CONNECT_INTENT => true])
            ->get(route('auth.sso.callback', ['provider' => 'github']))
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_disconnects_the_provider(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $this->withConfirmedPassword()
            ->actingAs($user)
            ->delete(route('settings.sso.destroy', ['provider' => 'github']))
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_refuses_to_disconnect_the_only_way_in(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();

        $this->withConfirmedPassword()
            ->actingAs($user)
            ->delete(route('settings.sso.destroy', ['provider' => 'google']))
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'google']);
    }

    #[Test]
    public function it_disconnects_when_a_passkey_is_left(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->withConfirmedPassword()
            ->actingAs($user->refresh())
            ->delete(route('settings.sso.destroy', ['provider' => 'google']))
            ->assertRedirect(route('settings.security.index'));

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_refuses_to_reach_the_provider_without_a_confirmed_password(): void
    {
        $this->offer('github');

        $this->actingAs(User::factory()->create())
            ->get(route('settings.sso.redirect', ['provider' => 'github']))
            ->assertRedirect(route('auth.confirmPassword.new'));
    }

    #[Test]
    public function it_refuses_to_disconnect_without_a_confirmed_password(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $this->actingAs($user)
            ->delete(route('settings.sso.destroy', ['provider' => 'github']))
            ->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github']);
    }

    #[Test]
    public function it_is_not_found_when_the_provider_is_unavailable(): void
    {
        config(['monica.sso_installed' => []]);

        $this->withConfirmedPassword()
            ->actingAs(User::factory()->create())
            ->get(route('settings.sso.redirect', ['provider' => 'github']))
            ->assertNotFound();
    }

    private function offer(string $provider): void
    {
        config([
            'monica.sso_installed' => SsoProviderEnum::values(),
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
