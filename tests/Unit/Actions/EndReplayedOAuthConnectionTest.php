<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EndReplayedOAuthConnection;
use App\Models\OAuthConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndReplayedOAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ends_the_connection_of_an_authorization_code_presented_again(): void
    {
        Queue::fake();

        $connection = OAuthConnection::factory()->create(['authorization_code_hash' => hash('sha256', 'pretzel-day')]);

        new EndReplayedOAuthConnection(authorizationCode: 'pretzel-day')->execute();

        $this->assertNotNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_ends_the_connection_of_a_replaced_refresh_token_presented_again(): void
    {
        Queue::fake();

        $connection = OAuthConnection::factory()->create([
            'previous_refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subMinute(),
        ]);

        new EndReplayedOAuthConnection(refreshToken: 'jello-stapler')->execute();

        $this->assertNotNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_leaves_the_connection_alone_when_the_replaced_refresh_token_comes_back_right_after_the_renewal(): void
    {
        $this->freezeTime();

        $connection = OAuthConnection::factory()->create([
            'previous_refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subSeconds(OAuthConnection::REPLAY_GRACE_IN_SECONDS - 1),
        ]);

        new EndReplayedOAuthConnection(refreshToken: 'jello-stapler')->execute();

        $this->assertNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_leaves_the_connection_of_the_current_refresh_token_alone(): void
    {
        $connection = OAuthConnection::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new EndReplayedOAuthConnection(refreshToken: 'jello-stapler')->execute();

        $this->assertNull($connection->refresh()->ended_at);
    }
}
