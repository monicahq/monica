<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Auth;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_a_token_for_good_credentials(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'device_name' => 'Dundie awards bot',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'expires_at']);
        $response->assertJsonPath('expires_at', null);
    }

    #[Test]
    public function it_names_the_key_after_the_device(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'device_name' => 'Dundie awards bot',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_starts_no_session(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ])->assertOk();

        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_wrong_password(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function it_says_the_same_thing_whatever_went_wrong(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        User::factory()->inactive()->create(['email' => 'dwight.schrute@dundermifflin.com']);
        User::factory()->singleSignOn()->create(['email' => 'jim.halpert@dundermifflin.com']);

        $wrongPassword = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'wrong-password',
        ]);
        $suspended = $this->json('POST', '/api/v1/login', [
            'email' => 'dwight.schrute@dundermifflin.com',
            'password' => 'password',
        ]);
        $singleSignOn = $this->json('POST', '/api/v1/login', [
            'email' => 'jim.halpert@dundermifflin.com',
            'password' => 'password',
        ]);
        $unknown = $this->json('POST', '/api/v1/login', [
            'email' => 'nobody@dundermifflin.com',
            'password' => 'password',
        ]);

        $wrongPassword->assertUnauthorized();
        $suspended->assertUnauthorized();
        $singleSignOn->assertUnauthorized();
        $unknown->assertUnauthorized();

        $message = $wrongPassword->json('message');

        $this->assertEquals($message, $suspended->json('message'));
        $this->assertEquals($message, $singleSignOn->json('message'));
        $this->assertEquals($message, $unknown->json('message'));
    }

    #[Test]
    public function it_validates_the_required_fields(): void
    {
        $response = $this->json('POST', '/api/v1/login', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function it_issues_no_token_when_the_second_factor_is_missing(): void
    {
        Queue::fake();

        User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function it_issues_no_token_when_the_second_factor_is_wrong(): void
    {
        Queue::fake();

        User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'code' => '000000',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function it_hands_back_a_token_when_the_second_factor_is_right(): void
    {
        Queue::fake();

        $secret = new Google2FA()->generateSecretKey();
        User::factory()->twoFactor($secret)->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'code' => new Google2FA()->getCurrentOtp($secret),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    #[Test]
    public function it_accepts_a_recovery_code_as_the_second_factor(): void
    {
        Queue::fake();

        User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'code' => 'scranton-1',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    #[Test]
    public function it_asks_an_account_protected_by_a_security_key_alone_for_a_recovery_code(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'two_factor_recovery_codes' => ['scranton-1'],
        ]);
        SecurityKey::factory()->for($user)->create();

        $withoutCode = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);
        $withRecoveryCode = $this->json('POST', '/api/v1/login', [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'code' => 'scranton-1',
        ]);

        $withoutCode->assertUnauthorized();
        $withRecoveryCode->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function it_revokes_the_token_it_was_called_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('Dundie awards bot');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('DELETE', '/api/v1/logout');

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    #[Test]
    public function it_leaves_the_other_tokens_of_the_user_working(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $revoked = $user->createToken('Dundie awards bot');
        $kept = $user->createToken('Second device');

        $this->withHeader('Authorization', 'Bearer '.$revoked->plainTextToken)
            ->json('DELETE', '/api/v1/logout')
            ->assertNoContent();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $kept->accessToken->id]);

        $this->withHeader('Authorization', 'Bearer '.$kept->plainTextToken)
            ->json('GET', '/api/v1/me')
            ->assertOk();
    }

    #[Test]
    public function it_stops_working_with_a_revoked_token(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('Dundie awards bot');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('DELETE', '/api/v1/logout')
            ->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->json('GET', '/api/v1/me')
            ->assertUnauthorized();
    }
}
