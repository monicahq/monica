<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\OAuth;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_out_the_phone_holding_the_refresh_token(): void
    {
        Queue::fake();

        $session = MobileSession::factory()->create([
            'user_id' => User::factory()->create()->id,
            'refresh_token_hash' => hash('sha256', 'jello-stapler'),
        ]);

        $this->post('/oauth/revoke', [
            'token' => 'jello-stapler',
            'token_type_hint' => 'refresh_token',
            'client_id' => MobileSession::CLIENT_ID,
        ])->assertOk()->assertContent('');

        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_answers_the_same_for_a_token_nobody_holds(): void
    {
        $this->post('/oauth/revoke', ['token' => 'beets', 'client_id' => MobileSession::CLIENT_ID])
            ->assertOk()
            ->assertContent('');
    }

    #[Test]
    public function it_asks_for_the_token_in_the_shape_of_an_oauth_error(): void
    {
        $this->postJson('/oauth/revoke', ['client_id' => MobileSession::CLIENT_ID])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_request');
    }
}
