<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthConnectionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const string ASSISTANT_REDIRECT_URI = 'https://claude.ai/mcp/auth_callback';

    private string $clientId = OAuthConnection::MOBILE_CLIENT_ID;

    private string $redirectUri = OAuthConnection::ANDROID_REDIRECT_URI;

    private string $scope = OAuthConnection::API_SCOPE;

    /** @return array<string, array{bool}> */
    public static function clients(): array
    {
        return [
            'the mobile app' => [false],
            'an assistant' => [true],
        ];
    }

    #[Test]
    #[DataProvider('clients')]
    public function a_sign_in_records_one_connection_with_the_scopes_of_its_token(bool $isAssistant): void
    {
        $this->useClient($isAssistant);
        $pam = User::factory()->create();

        $tokens = $this->signIn($pam);

        $this->reaches($tokens['access_token'])->assertOk();
        $connection = OAuthConnection::query()->sole();
        $this->assertSame($pam->id, $connection->user_id);
        $this->assertSame($this->clientId, $connection->client_id);
        $this->assertSame([$this->scope], $connection->scopes);
        $this->assertSame('Pixel 9', $connection->label);
    }

    #[Test]
    #[DataProvider('clients')]
    public function a_renewal_gives_new_tokens_and_retires_the_old_refresh_token(bool $isAssistant): void
    {
        $this->useClient($isAssistant);
        $tokens = $this->signIn(User::factory()->create());

        $renewed = $this->renew($tokens['refresh_token'])->assertOk()->json();

        $this->assertNotSame($tokens['refresh_token'], $renewed['refresh_token']);
        $this->forgetTheCaller();
        $this->reaches($renewed['access_token'])->assertOk();
        $this->assertNull(OAuthConnection::query()->sole()->ended_at);
        $this->assertSame(hash('sha256', $renewed['refresh_token']), OAuthConnection::query()->sole()->refresh_token_hash);
    }

    #[Test]
    #[DataProvider('clients')]
    public function an_old_refresh_token_presented_again_ends_its_connection(bool $isAssistant): void
    {
        $this->useClient($isAssistant);
        $tokens = $this->signIn(User::factory()->create());
        $renewed = $this->renew($tokens['refresh_token'])->json();

        $this->travel(OAuthConnection::REPLAY_GRACE_IN_SECONDS + 1)->seconds();
        $this->renew($tokens['refresh_token'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(OAuthConnection::query()->sole()->ended_at);
        $this->renew($renewed['refresh_token'])->assertStatus(400);
    }

    #[Test]
    #[DataProvider('clients')]
    public function two_renewals_sent_at_once_with_the_same_token_keep_the_app_connected(bool $isAssistant): void
    {
        $this->useClient($isAssistant);
        $tokens = $this->signIn(User::factory()->create());
        $renewed = $this->renew($tokens['refresh_token'])->json();

        $this->renew($tokens['refresh_token'])->assertStatus(400);

        $this->assertNull(OAuthConnection::query()->sole()->ended_at);
        $this->renew($renewed['refresh_token'])->assertOk();
    }

    #[Test]
    #[DataProvider('clients')]
    public function an_exchanged_code_presented_again_ends_its_connection(bool $isAssistant): void
    {
        $this->useClient($isAssistant);
        $tokens = $this->signIn(User::factory()->create());

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => str_repeat('a', 64),
            'code' => $tokens['code'],
        ])->assertStatus(400);

        $this->assertNotNull(OAuthConnection::query()->sole()->ended_at);
        $this->forgetTheCaller();
        $this->reaches($tokens['access_token'])->assertUnauthorized();
    }

    #[Test]
    public function a_renewal_naming_the_app_through_basic_authentication_moves_the_connection_along(): void
    {
        $tokens = $this->signIn(User::factory()->create());

        $renewed = $this->withHeader('Authorization', 'Basic '.base64_encode(OAuthConnection::MOBILE_CLIENT_ID.':'))
            ->postJson('/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens['refresh_token'],
                'scope' => OAuthConnection::API_SCOPE,
            ])->assertOk()->json();

        $this->assertSame(hash('sha256', $renewed['refresh_token']), OAuthConnection::query()->sole()->refresh_token_hash);
    }

    #[Test]
    public function a_phone_signed_out_loses_access_while_the_assistant_and_the_key_keep_it(): void
    {
        $dwight = User::factory()->create();
        $pixel = $this->signIn($dwight);
        $assistant = $this->oauthTokenFor(user: $dwight);
        $key = $dwight->createToken('Schrute Farms export', ['*'])->plainTextToken;

        $this->post('/oauth/revoke', ['token' => $pixel['refresh_token'], 'client_id' => OAuthConnection::MOBILE_CLIENT_ID])->assertOk();

        $this->withToken($pixel['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
        $this->forgetTheCaller();
        $this->withToken($key)->getJson('/api/v1/me')->assertOk();
        $this->assertFalse($assistant->refresh()->revoked);
    }

    private function useClient(bool $isAssistant): void
    {
        if (! $isAssistant) {
            return;
        }

        $this->clientId = (string) Client::factory()->asPublic()->create([
            'name' => 'Schrute Farms Assistant',
            'redirect_uris' => [self::ASSISTANT_REDIRECT_URI],
        ])->id;
        $this->redirectUri = self::ASSISTANT_REDIRECT_URI;
        $this->scope = Registrar::OAUTH_SCOPE;
    }

    /** @return array{access_token: string, refresh_token: string, code: string} */
    private function signIn(User $user): array
    {
        return $this->signInThroughOAuth(user: $user, clientId: $this->clientId, redirectUri: $this->redirectUri, scope: $this->scope);
    }

    private function reaches(string $accessToken): TestResponse
    {
        if ($this->scope === Registrar::OAUTH_SCOPE) {
            return $this->withToken($accessToken)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        }

        return $this->withToken($accessToken)->getJson('/api/v1/me');
    }

    private function renew(string $refreshToken): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'refresh_token' => $refreshToken,
            'scope' => $this->scope,
        ]);
    }

    private function forgetTheCaller(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
