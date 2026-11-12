<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\McpCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCallControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_calls_assistants_made(): void
    {
        $jim = User::factory()->create();

        McpCall::factory()->refused()->create([
            'account_id' => $jim->account_id,
            'user_id' => $jim->id,
            'subject_label' => 'Dwight Schrute',
        ]);

        $this->actingAs($jim)
            ->get(route('settings.mcpCalls.index'))
            ->assertOk()
            ->assertSee('MCP history')
            ->assertSee('Tried to read Dwight Schrute')
            ->assertSee('get-contact')
            ->assertSee('That contact does not exist.')
            ->assertSee('Parameters received')
            ->assertSee('Response returned')
            ->assertSee('Sensitive information is masked.')
            ->assertSee('Calls are kept for 90 days, then removed for good.');
    }

    #[Test]
    public function it_narrows_the_history_and_keeps_the_filters_across_pages(): void
    {
        $jim = User::factory()->create();

        McpCall::factory()->count(11)->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'tool' => 'list-vaults']);
        McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'subject_label' => 'Creed Bratton']);

        $this->actingAs($jim)
            ->get(route('settings.mcpCalls.index', ['tool' => 'list-vaults', 'size' => 10]))
            ->assertOk()
            ->assertDontSee('Read Creed Bratton')
            ->assertSee('tool=list-vaults', false)
            ->assertSee('page=2', false)
            ->assertSee('Show everything');
    }

    #[Test]
    public function it_refuses_a_filter_it_does_not_know(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.mcpCalls.index', ['period' => 'forever', 'connection' => 'fax:1', 'result' => 'lost']))
            ->assertSessionHasErrors(['period', 'connection', 'result']);
    }

    #[Test]
    public function it_does_not_exist_when_the_mcp_server_is_switched_off_for_the_account(): void
    {
        $jim = User::factory()->create();
        $jim->account->features()->deactivate('mcp-server');
        Feature::flushCache();

        $this->actingAs($jim)->get(route('settings.mcpCalls.index'))->assertNotFound();
        $this->actingAs($jim)->get(route('settings.security.index'))->assertDontSee(route('settings.mcpCalls.index'));
    }
}
