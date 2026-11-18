<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpRateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_no_limit_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $limit = $this->resolveLimit();

        $this->assertSame(PHP_INT_MAX, $limit->maxAttempts);
    }

    #[Test]
    public function it_allows_sixty_requests_a_minute_unless_told_otherwise(): void
    {
        config(['monica.self_hosted' => false]);

        $this->assertSame(60, $this->resolveLimit()->maxAttempts);
    }

    #[Test]
    public function it_counts_one_key_apart_from_another(): void
    {
        config(['monica.self_hosted' => false, 'monica.mcp_requests_per_minute' => 2]);
        $user = User::factory()->create();
        $spent = $user->createToken('Dundie awards bot');
        $untouched = $user->createToken('Deployment script');

        foreach (range(1, 3) as $ignored) {
            $this->withToken($spent->plainTextToken)->postJson('/mcp', $this->listTools());
        }

        $this->app['auth']->forgetGuards();

        $this->withToken($untouched->plainTextToken)
            ->postJson('/mcp', $this->listTools())
            ->assertOk();
    }

    #[Test]
    public function it_answers_too_many_requests_once_the_limit_is_spent(): void
    {
        config(['monica.self_hosted' => false, 'monica.mcp_requests_per_minute' => 2]);
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');

        foreach (range(1, 2) as $ignored) {
            $this->withToken($key->plainTextToken)->postJson('/mcp', $this->listTools())->assertOk();
        }

        $this->withToken($key->plainTextToken)
            ->postJson('/mcp', $this->listTools())
            ->assertStatus(429);
    }

    private function resolveLimit(): Limit
    {
        return RateLimiter::limiter('mcp')(Request::create('/mcp', 'POST'));
    }

    /** @return array<string, mixed> */
    private function listTools(): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
    }
}
