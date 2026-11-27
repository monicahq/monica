<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_renewal_gives_new_tokens_and_retires_the_old_refresh_token(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());

        $renewed = $this->renew($tokens['refresh_token'])->assertOk()->json();

        $this->assertNotSame($tokens['refresh_token'], $renewed['refresh_token']);
        $this->forgetTheCaller();
        $this->withToken($renewed['access_token'])->getJson('/api/v1/me')->assertOk();
        $this->assertNull(MobileSession::query()->sole()->ended_at);
    }

    #[Test]
    public function an_old_refresh_token_presented_again_ends_its_session(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());
        $renewed = $this->renew($tokens['refresh_token'])->json();

        $this->travel(MobileSession::REPLAY_GRACE_IN_SECONDS + 1)->seconds();
        $this->renew($tokens['refresh_token'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(MobileSession::query()->sole()->ended_at);
        $this->renew($renewed['refresh_token'])->assertStatus(400);
    }

    #[Test]
    public function two_renewals_sent_at_once_with_the_same_token_keep_the_phone_signed_in(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());
        $renewed = $this->renew($tokens['refresh_token'])->json();

        $this->renew($tokens['refresh_token'])->assertStatus(400);

        $this->assertNull(MobileSession::query()->sole()->ended_at);
        $this->renew($renewed['refresh_token'])->assertOk();
    }

    #[Test]
    public function a_renewal_naming_the_app_through_basic_authentication_moves_the_session_along(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());

        $renewed = $this->withHeader('Authorization', 'Basic '.base64_encode(MobileSession::CLIENT_ID.':'))
            ->postJson('/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens['refresh_token'],
                'scope' => MobileSession::SCOPE,
            ])->assertOk()->json();

        $this->assertSame(hash('sha256', $renewed['refresh_token']), MobileSession::query()->sole()->refresh_token_hash);
    }

    #[Test]
    public function an_exchanged_code_presented_again_ends_its_session(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => MobileSession::CLIENT_ID,
            'redirect_uri' => MobileSession::ANDROID_REDIRECT_URI,
            'code_verifier' => str_repeat('a', 64),
            'code' => $tokens['code'],
        ])->assertStatus(400);

        $this->assertNotNull(MobileSession::query()->sole()->ended_at);
        $this->forgetTheCaller();
        $this->withToken($tokens['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
    }

    #[Test]
    public function a_phone_signed_out_loses_access_while_the_assistant_and_the_key_keep_it(): void
    {
        $dwight = User::factory()->create();
        $pixel = $this->signInFromThePhone($dwight, 'Pixel 9');
        $assistant = $this->oauthTokenFor(user: $dwight);
        $key = $dwight->createToken('Schrute Farms export', ['*'])->plainTextToken;

        $this->post('/oauth/revoke', ['token' => $pixel['refresh_token'], 'client_id' => MobileSession::CLIENT_ID])->assertOk();

        $this->withToken($pixel['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
        $this->forgetTheCaller();
        $this->withToken($key)->getJson('/api/v1/me')->assertOk();
        $this->assertFalse($assistant->refresh()->revoked);
    }

    private function renew(string $refreshToken): TestResponse
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => MobileSession::CLIENT_ID,
            'refresh_token' => $refreshToken,
            'scope' => MobileSession::SCOPE,
        ]);
    }

    private function forgetTheCaller(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
