<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RenewOAuthConnection;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RenewOAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_follows_the_app_to_its_new_tokens(): void
    {
        $connection = OAuthConnection::factory()->create([
            'refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subDays(3),
        ]);

        new RenewOAuthConnection(
            presentedRefreshToken: 'jello-stapler',
            accessToken: $this->accessTokenWithId('pretzel-day'),
            refreshToken: 'dundie-award',
        )->execute();

        $connection->refresh();
        $this->assertSame('pretzel-day', $connection->access_token_id);
        $this->assertSame(hash('sha256', 'dundie-award'), $connection->refresh_token_hash);
        $this->assertSame(hash('sha256', 'jello-stapler'), $connection->previous_refresh_token_hash);
        $this->assertTrue($connection->last_used_at->isToday());
    }

    #[Test]
    public function it_refuses_a_connection_already_ended(): void
    {
        OAuthConnection::factory()->ended()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        $this->expectException(ModelNotFoundException::class);

        new RenewOAuthConnection(
            presentedRefreshToken: 'jello-stapler',
            accessToken: $this->accessTokenWithId('pretzel-day'),
            refreshToken: 'dundie-award',
        )->execute();
    }

    #[Test]
    public function it_marks_the_earlier_connection_of_an_assistant_as_used(): void
    {
        $dwight = User::factory()->create();
        $token = $this->oauthTokenFor(user: $dwight);
        $earlier = OAuthConnection::factory()->create([
            'user_id' => $dwight->id,
            'client_id' => $token->client_id,
            'access_token_id' => null,
            'refresh_token_hash' => null,
            'authorization_code_hash' => null,
            'last_used_at' => now()->subDays(3),
        ]);

        new RenewOAuthConnection(
            presentedRefreshToken: 'beet-harvest',
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'dundie-award',
        )->execute();

        $earlier->refresh();
        $this->assertTrue($earlier->last_used_at->isToday());
        $this->assertNull($earlier->refresh_token_hash);
    }

    #[Test]
    public function it_refuses_a_renewal_that_matches_no_connection(): void
    {
        $token = $this->oauthTokenFor(user: User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new RenewOAuthConnection(
            presentedRefreshToken: 'beet-harvest',
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'dundie-award',
        )->execute();
    }
}
