<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EndReplayedMobileSession;
use App\Models\MobileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndReplayedMobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ends_the_session_of_an_authorization_code_presented_again(): void
    {
        Queue::fake();

        $session = MobileSession::factory()->create(['authorization_code_hash' => hash('sha256', 'pretzel-day')]);

        new EndReplayedMobileSession(authorizationCode: 'pretzel-day')->execute();

        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_ends_the_session_of_a_replaced_refresh_token_presented_again(): void
    {
        Queue::fake();

        $session = MobileSession::factory()->create([
            'previous_refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subMinute(),
        ]);

        new EndReplayedMobileSession(refreshToken: 'jello-stapler')->execute();

        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_leaves_the_session_alone_when_the_replaced_refresh_token_comes_back_right_after_the_renewal(): void
    {
        $session = MobileSession::factory()->create([
            'previous_refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subSeconds(MobileSession::REPLAY_GRACE_IN_SECONDS - 1),
        ]);

        new EndReplayedMobileSession(refreshToken: 'jello-stapler')->execute();

        $this->assertNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_leaves_the_session_of_the_current_refresh_token_alone(): void
    {
        $session = MobileSession::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new EndReplayedMobileSession(refreshToken: 'jello-stapler')->execute();

        $this->assertNull($session->refresh()->ended_at);
    }
}
