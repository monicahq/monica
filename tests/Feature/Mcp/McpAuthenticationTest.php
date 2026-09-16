<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_a_request_carrying_no_key(): void
    {
        $response = $this->postJson('/api/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_a_revoked_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $key->accessToken->delete();

        $response = $this->withToken($key->plainTextToken)->postJson('/api/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_an_expired_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot', ['*'], now()->subDay());

        $response = $this->withToken($key->plainTextToken)->postJson('/api/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_admits_a_live_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');

        $response = $this->withToken($key->plainTextToken)->postJson('/api/mcp', $this->listTools());

        $response->assertOk();
    }

    /** @return array<string, mixed> */
    private function listTools(): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
    }
}
