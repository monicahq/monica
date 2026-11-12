<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpTransportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tells_a_client_that_only_post_carries_a_message(): void
    {
        $this->get('/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
        $this->delete('/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
    }

    #[Test]
    public function it_answers_a_json_rpc_envelope_naming_the_server(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1.0']],
        ]);

        $response->assertOk()
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('result.serverInfo.name', 'Monica');
    }
}
