<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Http\Middleware\SetApiLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpTransportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tells_a_client_that_only_post_carries_a_message(): void
    {
        $this->get('/api/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
        $this->delete('/api/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
    }

    #[Test]
    public function it_answers_a_json_rpc_envelope_naming_the_server(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');

        $response = $this->withToken($key->plainTextToken)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1.0']],
        ]);

        $response->assertOk()
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('result.serverInfo.name', 'Erica');
    }

    #[Test]
    public function it_resolves_the_locale_the_way_the_json_api_does(): void
    {
        $this->assertContains(SetApiLocale::class, $this->middlewareOnTheEndpoint());
    }

    #[Test]
    public function it_is_limited_per_key_rather_than_per_user(): void
    {
        $middleware = $this->middlewareOnTheEndpoint();

        $this->assertContains('Illuminate\Routing\Middleware\ThrottleRequests:mcp', $middleware);
        $this->assertNotContains('Illuminate\Routing\Middleware\ThrottleRequests:api', $middleware);
    }

    /** @return array<int, string> */
    private function middlewareOnTheEndpoint(): array
    {
        $route = collect(Router::getRoutes()->getRoutes())
            ->first(fn (Route $route): bool => $route->uri() === 'api/mcp' && in_array('POST', $route->methods(), true));

        return array_values(array_filter(
            Router::gatherRouteMiddleware($route),
            fn (mixed $middleware): bool => is_string($middleware),
        ));
    }
}
