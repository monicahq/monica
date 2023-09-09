<?php

namespace Tests\Feature\Controllers\Auth;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Tests\Helpers\GuzzleMock;
use Tests\TestCase;

class SocialiteCallbackControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function mockSocialite($client = null): void
    {
        config(['auth.login_providers' => 'test']);

        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        $socialite = app(SocialiteFactory::class);
        $socialite->extend(
            'test',
            function () use ($client) {
                $provider = new GithubProvider(app('request'), 'client_id', 'client_secret', 'redirect_url', []);
                if ($client) {
                    $provider->setHttpClient($client);
                }

                return $provider;
            }
        );
    }

    private function getMock(): GuzzleMock
    {
        return new GuzzleMock([
            'https://github.com/login/oauth/access_token' => [
                'access_token' => 'token',
            ],
            'https://api.github.com/user' => [
                'id' => 12345,
                'login' => 'customer',
                'name' => 'Customer Legit',
                'avatar_url' => '',
                'node_id' => '',
            ],
            'https://api.github.com/user/emails' => [[
                'primary' => true,
                'verified' => true,
                'email' => 'customer@legit.com',
            ]],
        ]);
    }

    /** @test */
    public function it_get_redirect_url(): void
    {
        $this->mockSocialite();
        Socialite::driver('test')->stateless();

        $response = $this->get('/auth/test');

        $response->assertStatus(302);
        $response->assertRedirect('https://github.com/login/oauth/authorize?client_id=client_id&redirect_uri=redirect_url&scope=user%3Aemail&response_type=code');
    }

    /** @test */
    public function it_get_redirect_url_inertia(): void
    {
        $this->mockSocialite();
        Socialite::driver('test')->stateless();

        $response = $this->get('/auth/test', [
            'X-Inertia' => true,
            'X-Inertia-Version' => (new HandleInertiaRequests)->version(request()) ?? '',
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', 'https://github.com/login/oauth/authorize?client_id=client_id&redirect_uri=redirect_url&scope=user%3Aemail&response_type=code');
    }

    /** @test */
    public function it_get_user_created(): void
    {
        $mock = $this->getMock();
        $this->mockSocialite($mock->getClient());

        session()->put('state', 'state');

        $response = $this->get('/auth/test/callback?code=thecode&state=state');
        $response->assertStatus(302);
        $response->assertRedirect(config('app.url'));

        $mock->assertResponses();

        $this->assertDatabaseHas('users', [
            'email' => 'customer@legit.com',
        ]);
        $user = User::firstWhere('email', 'customer@legit.com');
        $this->assertDatabaseHas('user_tokens', [
            'driver_id' => 12345,
            'driver' => 'test',
            'user_id' => $user->id,
            'email' => 'customer@legit.com',
            'format' => 'oauth2',
            'token' => 'token',
        ]);
    }

    /** @test */
    public function it_associate_token_to_logged_user(): void
    {
        $mock = $this->getMock();
        $this->mockSocialite($mock->getClient());

        $user = User::factory()->create(['email' => 'customer@legit.com']);
        $this->actingAs($user);

        session()->put('state', 'state');

        $response = $this->get('/auth/test/callback?code=thecode&state=state');
        $response->assertStatus(302);
        $response->assertRedirect(config('app.url'));

        $mock->assertResponses();

        $this->assertDatabaseHas('user_tokens', [
            'driver_id' => 12345,
            'driver' => 'test',
            'user_id' => $user->id,
            'email' => 'customer@legit.com',
            'format' => 'oauth2',
            'token' => 'token',
        ]);
    }

    /** @test */
    public function it_wont_associate_token_if_user_already_exist(): void
    {
        $mock = $this->getMock();
        $this->mockSocialite($mock->getClient());

        $user = User::factory()->create(['email' => 'customer@legit.com']);

        session()->put('state', 'state');

        $response = $this->get('/auth/test/callback?code=thecode&state=state');

        $mock->assertResponses();

        $response->assertStatus(302);
        $response->assertRedirect(config('app.url'));

        $this->assertDatabaseMissing('user_tokens', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_wont_associate_token_if_another_user_already_connected(): void
    {
        $mock = $this->getMock();
        $this->mockSocialite($mock->getClient());

        $user1 = User::factory()->create();
        UserToken::factory()->create([
            'driver_id' => 12345,
            'driver' => 'test',
            'user_id' => $user1->id,
            'email' => 'customer@legit.com',
            'format' => 'oauth2',
            'token' => 'token',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        session()->put('state', 'state');

        $response = $this->get('/auth/test/callback?code=thecode&state=state');

        $mock->assertResponses();

        $response->assertStatus(302);
        $response->assertRedirect(config('app.url'));

        $this->assertDatabaseMissing('user_tokens', [
            'user_id' => $user->id,
        ]);
    }
}
