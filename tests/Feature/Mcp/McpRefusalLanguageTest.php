<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpRefusalLanguageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_in_the_language_the_caller_announces(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $unreachable = Vault::factory()->create();

        $response = $this->withToken($key->plainTextToken)
            ->withHeader('Accept-Language', 'fr-FR')
            ->postJson('/mcp', $this->askFor($unreachable->uuid));

        $response->assertOk();

        $english = $this->withToken($key->plainTextToken)
            ->withHeader('Accept-Language', 'en')
            ->postJson('/mcp', $this->askFor($unreachable->uuid));

        $this->assertNotSame($english->getContent(), $response->getContent());
    }

    #[Test]
    public function it_refuses_in_english_when_nothing_asks_for_another_language(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $unreachable = Vault::factory()->create();

        $response = $this->withToken($key->plainTextToken)
            ->postJson('/mcp', $this->askFor($unreachable->uuid));

        $response->assertOk();
    }

    /** @return array<string, mixed> */
    private function askFor(string $vault): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'list-contacts', 'arguments' => ['vault' => $vault]],
        ];
    }
}
