<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\McpCall;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCallControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'started_at_in_milliseconds',
            'duration_in_milliseconds',
            'connection' => ['type', 'id', 'name'],
            'tool',
            'operation',
            'vault',
            'subject',
            'result',
            'error',
            'json_rpc_id',
            'ip_address',
            'parameters',
            'parameters_truncated',
            'response',
            'response_truncated',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_calls_of_the_account_newest_first(): void
    {
        $jim = User::factory()->create();
        $michael = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $jim->account_id]);

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'tool' => 'list-vaults', 'started_at' => now()->subHour()]);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'vault_id' => $vault->id, 'started_at' => now()]);
        McpCall::factory()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id]);

        Sanctum::actingAs($jim);

        $this->json('GET', '/api/v1/mcp-calls')
            ->assertOk()
            ->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.tool', 'get-contact')
            ->assertJsonPath('data.0.attributes.vault.id', $vault->uuid)
            ->assertJsonPath('data.1.attributes.tool', 'list-vaults')
            ->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_narrows_the_list_with_the_same_filters_as_the_screen(): void
    {
        $jim = User::factory()->create();

        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id]);
        McpCall::factory()->refused()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'tool' => 'delete-life-event']);

        Sanctum::actingAs($jim);

        $this->json('GET', '/api/v1/mcp-calls?result=refused&tool=delete-life-event&period=week&connection=api_key:1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.tool', 'delete-life-event');
    }

    #[Test]
    public function it_refuses_a_filter_it_does_not_know(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/mcp-calls?period=forever&result=lost')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period', 'result']);
    }

    #[Test]
    public function it_shows_one_call_with_its_error(): void
    {
        $jim = User::factory()->create();
        $call = McpCall::factory()->refused()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id]);

        Sanctum::actingAs($jim);

        $this->json('GET', "/api/v1/mcp-calls/{$call->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data' => $this->jsonStructure])
            ->assertJsonPath('data.id', $call->uuid)
            ->assertJsonPath('data.attributes.result', 'refused')
            ->assertJsonPath('data.attributes.error.code', -32602);
    }

    #[Test]
    public function it_answers_not_found_for_a_call_of_another_account(): void
    {
        $michael = User::factory()->create();
        $call = McpCall::factory()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/mcp-calls/{$call->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_when_the_mcp_server_is_switched_off_for_the_account(): void
    {
        $jim = User::factory()->create();
        $jim->account->features()->deactivate('mcp-server');
        Feature::flushCache();

        Sanctum::actingAs($jim);

        $this->json('GET', '/api/v1/mcp-calls')->assertNotFound();
    }
}
