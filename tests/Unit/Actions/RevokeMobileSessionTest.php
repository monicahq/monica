<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RevokeMobileSession;
use App\Models\MobileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeMobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_out_the_phone_holding_the_refresh_token(): void
    {
        Queue::fake();

        $session = MobileSession::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new RevokeMobileSession(clientId: MobileSession::CLIENT_ID, refreshToken: 'jello-stapler')->execute();

        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_changes_nothing_for_a_token_nobody_holds(): void
    {
        $session = MobileSession::factory()->create();

        new RevokeMobileSession(clientId: MobileSession::CLIENT_ID, refreshToken: 'beets')->execute();

        $this->assertNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_changes_nothing_when_another_client_names_the_token(): void
    {
        $session = MobileSession::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new RevokeMobileSession(clientId: 'schrute-farms', refreshToken: 'jello-stapler')->execute();

        $this->assertNull($session->refresh()->ended_at);
    }
}
