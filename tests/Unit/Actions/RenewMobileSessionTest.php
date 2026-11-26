<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RenewMobileSession;
use App\Models\MobileSession;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RenewMobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_follows_the_phone_to_its_new_tokens(): void
    {
        $session = MobileSession::factory()->create([
            'refresh_token_hash' => hash('sha256', 'jello-stapler'),
            'last_used_at' => now()->subDays(3),
        ]);

        new RenewMobileSession(
            presentedRefreshToken: 'jello-stapler',
            accessToken: $this->accessTokenWithId('pretzel-day'),
            refreshToken: 'dundie-award',
        )->execute();

        $session->refresh();
        $this->assertSame('pretzel-day', $session->access_token_id);
        $this->assertSame(hash('sha256', 'dundie-award'), $session->refresh_token_hash);
        $this->assertSame(hash('sha256', 'jello-stapler'), $session->previous_refresh_token_hash);
        $this->assertTrue($session->last_used_at->isToday());
    }

    #[Test]
    public function it_refuses_a_session_already_ended(): void
    {
        MobileSession::factory()->ended()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        $this->expectException(ModelNotFoundException::class);

        new RenewMobileSession(
            presentedRefreshToken: 'jello-stapler',
            accessToken: $this->accessTokenWithId('pretzel-day'),
            refreshToken: 'dundie-award',
        )->execute();
    }
}
