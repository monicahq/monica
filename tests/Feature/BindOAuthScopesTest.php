<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BindOAuthScopesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_the_api_scope_to_an_assistant(): void
    {
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);

        $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: OAuthConnection::API_SCOPE))
            ->assertRedirectContains('https://claude.ai/callback?error=invalid_scope');
    }

    #[Test]
    public function it_sends_a_refusal_nowhere_the_client_did_not_register(): void
    {
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);

        $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://dunder-mifflin.example/steal', scope: OAuthConnection::API_SCOPE))
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');
    }

    #[Test]
    public function it_refuses_the_mcp_scope_to_the_mobile_app(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: OAuthConnection::MOBILE_CLIENT_ID, redirectUri: OAuthConnection::ANDROID_REDIRECT_URI, scope: Registrar::OAUTH_SCOPE));

        $response->assertRedirectContains(OAuthConnection::ANDROID_REDIRECT_URI);
        $response->assertRedirectContains('error=invalid_scope');
        $response->assertRedirectContains('state=beet-harvest');
    }

    #[Test]
    public function it_sends_the_refusal_back_to_the_ios_app(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: OAuthConnection::MOBILE_CLIENT_ID, redirectUri: OAuthConnection::IOS_REDIRECT_URI, scope: Registrar::OAUTH_SCOPE));

        $response->assertRedirect(OAuthConnection::IOS_REDIRECT_URI.'?error=invalid_scope&state=beet-harvest');
    }

    #[Test]
    public function it_refuses_the_api_scope_on_a_renewal_by_an_assistant(): void
    {
        $assistant = Client::factory()->asPublic()->create();

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $assistant->id,
            'refresh_token' => 'beets',
            'scope' => OAuthConnection::API_SCOPE,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_scope');
    }

    #[Test]
    public function it_shows_an_assistant_the_consent_page(): void
    {
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);

        $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: Registrar::OAUTH_SCOPE))
            ->assertOk()
            ->assertSee('Connect Schrute Farms Assistant');
    }

    #[Test]
    public function it_asks_an_assistant_already_allowed_again(): void
    {
        $pam = User::factory()->create();
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);
        $this->oauthTokenFor(user: $pam, client: $assistant);

        $this->actingAs($pam)
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: Registrar::OAUTH_SCOPE))
            ->assertOk()
            ->assertSee('Connect Schrute Farms Assistant');
    }

    #[Test]
    public function it_shows_the_consent_page_when_asked_to_show_nothing(): void
    {
        $pam = User::factory()->create();
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);
        $this->oauthTokenFor(user: $pam, client: $assistant);

        $this->actingAs($pam)
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: Registrar::OAUTH_SCOPE).'&prompt=none')
            ->assertOk()
            ->assertSee('Connect Schrute Farms Assistant');
    }

    private function authorizationUrl(string $clientId, string $redirectUri, string $scope): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => 'beet-harvest',
            'code_challenge' => strtr(rtrim(base64_encode(hash('sha256', 'dunder-mifflin-verifier-with-enough-characters-to-pass', true)), '='), '+/', '-_'),
            'code_challenge_method' => 'S256',
        ]);
    }
}
