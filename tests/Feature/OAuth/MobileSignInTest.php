<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSignInTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_phone_reaches_the_api_once_the_person_allowed_it(): void
    {
        $pam = User::factory()->create(['email' => 'pam@dundermifflin.com']);

        $tokens = $this->signInFromThePhone($pam);

        $this->withToken($tokens['access_token'])->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.attributes.email', 'pam@dundermifflin.com');

        $session = MobileSession::query()->sole();
        $this->assertSame($pam->id, $session->user_id);
        $this->assertSame('Pixel 9', $session->device_name);
        $this->assertSame(3600, $this->tokenLifetime($tokens['access_token']));
    }

    #[Test]
    public function it_shows_the_consent_page_even_when_the_person_allowed_the_app_before(): void
    {
        $pam = User::factory()->create();
        $this->signInFromThePhone($pam);

        $this->actingAs($pam)->get($this->authorizationUrl())
            ->assertOk()
            ->assertSee('Allow Monica for Android to reach your account?');
    }

    #[Test]
    public function it_shows_the_consent_page_whatever_the_mcp_server_switch(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->deactivate('mcp-server');

        $this->actingAs($pam)->get($this->authorizationUrl())->assertOk();
    }

    #[Test]
    public function it_refuses_a_code_exchanged_with_the_wrong_verifier(): void
    {
        $pam = User::factory()->create();
        $code = $this->approvedCode($pam);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => MobileSession::CLIENT_ID,
            'redirect_uri' => MobileSession::REDIRECT_URI,
            'code_verifier' => Str::random(64),
            'code' => $code,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->assertSame(0, MobileSession::query()->count());
    }

    #[Test]
    public function it_refuses_a_return_address_that_differs_by_one_character(): void
    {
        $pam = User::factory()->create();

        $this->actingAs($pam)->get($this->authorizationUrl(redirectUri: MobileSession::REDIRECT_URI.'s'))
            ->assertStatus(401);
    }

    #[Test]
    public function it_sends_the_phone_back_empty_handed_when_the_person_denies_it(): void
    {
        $pam = User::factory()->create();
        $this->actingAs($pam)->get($this->authorizationUrl());

        $response = $this->delete('/oauth/authorize', [
            'state' => '',
            'client_id' => MobileSession::CLIENT_ID,
            'auth_token' => session('authToken'),
        ]);

        $response->assertRedirectContains(MobileSession::REDIRECT_URI);
        $response->assertRedirectContains('error=access_denied');
        $this->assertSame(0, MobileSession::query()->count());
    }

    #[Test]
    public function the_token_of_the_phone_does_not_reach_the_mcp_server(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());

        $this->withToken($tokens['access_token'])->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertForbidden();
    }

    #[Test]
    public function the_token_of_an_assistant_does_not_reach_the_api(): void
    {
        Passport::actingAs(User::factory()->create(), [Registrar::OAUTH_SCOPE]);

        $this->getJson('/api/v1/me')->assertForbidden();
    }

    private function approvedCode(User $user): string
    {
        $this->actingAs($user)->get($this->authorizationUrl());

        $approval = $this->post('/oauth/authorize', [
            'state' => '',
            'client_id' => MobileSession::CLIENT_ID,
            'auth_token' => session('authToken'),
        ]);

        parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $query['code'];
    }

    private function authorizationUrl(string $redirectUri = MobileSession::REDIRECT_URI): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => MobileSession::CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => MobileSession::SCOPE,
            'state' => 'beet-harvest',
            'code_challenge' => strtr(rtrim(base64_encode(hash('sha256', Str::random(64), true)), '='), '+/', '-_'),
            'code_challenge_method' => 'S256',
        ]);
    }

    private function tokenLifetime(string $accessToken): int
    {
        $payload = json_decode((string) base64_decode(strtr(explode('.', $accessToken)[1], '-_', '+/')), true);

        return (int) round($payload['exp'] - $payload['iat']);
    }
}
