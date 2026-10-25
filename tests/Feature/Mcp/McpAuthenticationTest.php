<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_a_request_carrying_no_key(): void
    {
        $response = $this->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_a_revoked_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $key->accessToken->delete();

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_an_expired_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot', ['*'], now()->subDay());

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_the_key_of_a_deactivated_person(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');
        $user->update(['is_active' => false]);

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_a_browser_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/mcp', $this->listTools());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_refuses_an_assistant_whose_token_lacks_the_scope_of_the_mcp_server(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        $response = $this->postJson('/mcp', $this->listTools());

        $response->assertForbidden();
    }

    #[Test]
    public function it_admits_an_assistant_whose_token_holds_the_scope_of_the_mcp_server(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, [Registrar::OAUTH_SCOPE]);

        $response = $this->postJson('/mcp', $this->listTools());

        $response->assertOk();
    }

    #[Test]
    public function it_admits_a_live_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot');

        $response = $this->withToken($key->plainTextToken)->postJson('/mcp', $this->listTools());

        $response->assertOk();
    }

    #[Test]
    public function it_answers_not_found_to_an_account_the_mcp_server_is_switched_off_for(): void
    {
        $pam = User::factory()->create();
        $dwight = User::factory()->create();
        $pamsKey = $pam->createToken('Pam’s assistant')->plainTextToken;
        $dwightsKey = $dwight->createToken('Dwight’s assistant')->plainTextToken;
        $pam->account->features()->deactivate('mcp-server');
        Feature::flushCache();

        $this->withToken($pamsKey)->postJson('/mcp', $this->listTools())->assertNotFound();

        $this->app['auth']->forgetGuards();

        $this->withToken($dwightsKey)->postJson('/mcp', $this->listTools())->assertOk();
    }

    /** @return array<string, mixed> */
    private function listTools(): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
    }
}
