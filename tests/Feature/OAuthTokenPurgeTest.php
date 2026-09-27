<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\RefreshToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthTokenPurgeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_refresh_token_of_an_assistant_idle_for_longer_than_the_purge_waits(): void
    {
        $token = $this->oauthTokenFor(user: User::factory()->create(), expiresAt: now()->subDays(8)->toImmutable());

        $this->artisan('passport:purge')->assertSuccessful();

        $this->assertDatabaseMissing('oauth_access_tokens', ['id' => $token->id]);
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $token->id)->exists());
    }

    #[Test]
    public function it_purges_a_refresh_token_once_it_has_expired_too(): void
    {
        $token = $this->oauthTokenFor(
            user: User::factory()->create(),
            expiresAt: now()->subDays(40)->toImmutable(),
            refreshExpiresAt: now()->subDays(10)->toImmutable(),
        );

        $this->artisan('passport:purge')->assertSuccessful();

        $this->assertFalse(RefreshToken::query()->where('access_token_id', $token->id)->exists());
    }
}
