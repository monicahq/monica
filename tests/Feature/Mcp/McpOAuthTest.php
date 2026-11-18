<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    private const string REDIRECT_URI = 'https://claude.ai/mcp/auth_callback';

    private string $codeVerifier;

    #[Test]
    public function it_tells_a_client_without_a_token_where_to_authorize(): void
    {
        $response = $this->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
        $this->assertStringContainsString(
            'resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"',
            (string) $response->headers->get('WWW-Authenticate'),
        );

        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers.0', url('/'));

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
            ->assertJsonPath('token_endpoint', route('passport.token'))
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256');
    }

    #[Test]
    public function it_asks_the_person_before_connecting_an_assistant(): void
    {
        $user = User::factory()->create(['email' => 'dwight@dundermifflin.com']);
        $clientId = $this->registerClient();

        $response = $this->actingAs($user)->get($this->authorizationUrl($clientId));

        $response->assertOk();
        $response->assertSee('Schrute Farms Assistant');
        $response->assertSee('dwight@dundermifflin.com');
        $response->assertSee('claude.ai');
        $response->assertSee('action="'.route('passport.authorizations.approve').'"', false);
    }

    #[Test]
    public function it_answers_not_found_when_the_mcp_server_is_switched_off_for_the_person(): void
    {
        $pam = User::factory()->create();
        $clientId = $this->registerClient();
        $pam->account->features()->deactivate('mcp-server');
        Feature::flushCache();

        $this->actingAs($pam)->get($this->authorizationUrl($clientId))->assertNotFound();
    }

    #[Test]
    public function it_admits_an_assistant_the_person_allowed(): void
    {
        $user = User::factory()->create();

        $accessToken = $this->authorizeAssistant($user);

        $response = $this->withToken($accessToken)->postJson('/mcp', $this->listTools());

        $response->assertOk();
        $response->assertJsonPath('result.tools.0.name', fn (string $name): bool => $name !== '');
    }

    #[Test]
    public function it_sends_the_assistant_back_empty_handed_when_the_person_denies_it(): void
    {
        $user = User::factory()->create();
        $clientId = $this->registerClient();
        $this->actingAs($user)->get($this->authorizationUrl($clientId));

        $response = $this->delete('/oauth/authorize', [
            'state' => '',
            'client_id' => $clientId,
            'auth_token' => session('authToken'),
        ]);

        $response->assertRedirectContains(self::REDIRECT_URI);
        $response->assertRedirectContains('error=access_denied');
    }

    #[Test]
    public function it_refuses_an_assistant_whose_token_was_revoked(): void
    {
        $user = User::factory()->create();
        $accessToken = $this->authorizeAssistant($user);
        $user->oauthAccessTokens()->sole()->revoke();

        $response = $this->withToken($accessToken)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_the_assistant_of_a_deactivated_person(): void
    {
        $user = User::factory()->create();
        $accessToken = $this->authorizeAssistant($user);
        $user->update(['is_active' => false]);

        $response = $this->withToken($accessToken)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_records_the_changes_an_assistant_makes_under_its_own_name(): void
    {
        $user = User::factory()->create();
        $accessToken = $this->authorizeAssistant($user);

        $this->withToken($accessToken)->postJson('/mcp', $this->listTools());

        $this->assertSame('Schrute Farms Assistant', request()->attributes->get('log_source_detail'));
    }

    #[Test]
    public function it_signs_a_feedback_report_with_the_name_of_the_assistant(): void
    {
        $user = User::factory()->create();
        $accessToken = $this->authorizeAssistant($user);

        $this->withToken($accessToken)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => ['name' => 'submit-feedback', 'arguments' => [
                'kind' => 'bug',
                'severity' => 'minor',
                'title' => 'Beet harvest reminders are refused',
                'observed' => 'The reminder is refused.',
                'expected' => 'It is created.',
                'proposed_fix' => 'Accept it.',
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('agent_feedback_reports', ['user_id' => $user->id, 'connection_name' => 'Schrute Farms Assistant']);
    }

    private function registerClient(): string
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Schrute Farms Assistant',
            'redirect_uris' => [self::REDIRECT_URI],
        ]);

        $response->assertCreated();

        return $response->json('client_id');
    }

    private function authorizationUrl(string $clientId): string
    {
        $this->codeVerifier = Str::random(64);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $this->codeVerifier, true)), '='), '+/', '-_');

        return '/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'beet-harvest',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    private function authorizeAssistant(User $user): string
    {
        $clientId = $this->registerClient();
        $this->actingAs($user)->get($this->authorizationUrl($clientId));

        $approval = $this->post('/oauth/authorize', [
            'state' => '',
            'client_id' => $clientId,
            'auth_token' => session('authToken'),
        ]);

        parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT_URI,
            'code_verifier' => $this->codeVerifier,
            'code' => $query['code'],
        ]);

        $response->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $response->json('access_token');
    }

    /** @return array<string, mixed> */
    private function listTools(): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
    }
}
