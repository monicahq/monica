<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
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
            ->postJson('/api/mcp', $this->askFor($unreachable->uuid));

        $response->assertOk();
        $this->assertStringContainsString('Ce coffre n’existe pas.', $response->getContent());
    }

    #[Test]
    public function it_resolves_a_locale_exactly_as_the_json_api_does(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $unreachable = Vault::factory()->create();

        $this->withToken($key->plainTextToken)
            ->withHeader('Accept-Language', 'de-DE')
            ->json('GET', route('api.v1.me'))
            ->assertOk();
        $overTheJsonApi = App::getLocale();

        $this->app['auth']->forgetGuards();

        $this->withToken($key->plainTextToken)
            ->withHeader('Accept-Language', 'de-DE')
            ->postJson('/api/mcp', $this->askFor($unreachable->uuid))
            ->assertOk();

        $this->assertSame($overTheJsonApi, App::getLocale());
        $this->assertSame('de_DE', App::getLocale());
    }

    #[Test]
    public function it_refuses_in_english_when_nothing_asks_for_another_language(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $unreachable = Vault::factory()->create();

        $response = $this->withToken($key->plainTextToken)
            ->postJson('/api/mcp', $this->askFor($unreachable->uuid));

        $response->assertOk();
        $this->assertStringContainsString('That vault does not exist.', $response->getContent());
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
