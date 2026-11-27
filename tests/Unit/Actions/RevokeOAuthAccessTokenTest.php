<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RevokeOAuthAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\RefreshToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeOAuthAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_revokes_the_access_token_and_its_refresh_token(): void
    {
        $token = $this->oauthTokenFor(User::factory()->create());

        new RevokeOAuthAccessToken(accessTokenId: (string) $token->id)->execute();

        $this->assertTrue($token->refresh()->revoked);
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $token->id)->sole()->revoked);
    }

    #[Test]
    public function it_leaves_the_other_tokens_of_the_person_alone(): void
    {
        $user = User::factory()->create();
        $revoked = $this->oauthTokenFor($user);
        $kept = $this->oauthTokenFor($user);

        new RevokeOAuthAccessToken(accessTokenId: (string) $revoked->id)->execute();

        $this->assertFalse($kept->refresh()->revoked);
        $this->assertFalse(RefreshToken::query()->where('access_token_id', $kept->id)->sole()->revoked);
    }
}
