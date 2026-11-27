<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileSession;
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
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: MobileSession::SCOPE))
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');
    }

    #[Test]
    public function it_refuses_the_mcp_scope_to_the_mobile_app(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: MobileSession::CLIENT_ID, redirectUri: MobileSession::ANDROID_REDIRECT_URI, scope: Registrar::OAUTH_SCOPE));

        $response->assertRedirectContains(MobileSession::ANDROID_REDIRECT_URI);
        $response->assertRedirectContains('error=invalid_scope');
        $response->assertRedirectContains('state=beet-harvest');
    }

    #[Test]
    public function it_sends_the_refusal_back_to_the_ios_app(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: MobileSession::CLIENT_ID, redirectUri: MobileSession::IOS_REDIRECT_URI, scope: Registrar::OAUTH_SCOPE));

        $response->assertRedirect(MobileSession::IOS_REDIRECT_URI.'?error=invalid_scope&state=beet-harvest');
    }

    #[Test]
    public function it_refuses_the_api_scope_on_a_renewal_by_an_assistant(): void
    {
        $assistant = Client::factory()->asPublic()->create();

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $assistant->id,
            'refresh_token' => 'beets',
            'scope' => MobileSession::SCOPE,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_scope');
    }

    #[Test]
    public function it_leaves_the_flow_of_an_assistant_as_it_was(): void
    {
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant', 'redirect_uris' => ['https://claude.ai/callback']]);

        $this->actingAs(User::factory()->create())
            ->get($this->authorizationUrl(clientId: (string) $assistant->id, redirectUri: 'https://claude.ai/callback', scope: Registrar::OAUTH_SCOPE))
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
