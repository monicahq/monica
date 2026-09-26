<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListLogs;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLogsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_activity_of_the_account_newest_first(): void
    {
        $user = User::factory()->create();
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.1']);
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.2']);

        EricaServer::actingAs($user)->tool(ListLogs::class, ['per_page' => 1])
            ->assertOk()
            ->assertSee('10.0.0.2')
            ->assertDontSee('10.0.0.1')
            ->assertSee('"total":2');
    }

    #[Test]
    public function it_leaves_out_the_activity_of_another_account(): void
    {
        $user = User::factory()->create();
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.1']);
        Log::factory()->create(['ip_address' => '10.0.0.9']);

        EricaServer::actingAs($user)->tool(ListLogs::class)
            ->assertOk()
            ->assertSee('10.0.0.1')
            ->assertDontSee('10.0.0.9');
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.1']);
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.2']);
        Log::factory()->create(['account_id' => $user->account_id, 'ip_address' => '10.0.0.3']);

        EricaServer::actingAs($user)->tool(ListLogs::class, ['page' => 2, 'per_page' => 2])
            ->assertOk()
            ->assertSee('10.0.0.1')
            ->assertDontSee('10.0.0.3')
            ->assertSee('"page":2');
    }

    #[Test]
    public function it_brings_an_absurd_page_size_back_into_range(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(ListLogs::class, ['per_page' => 1000])
            ->assertOk()
            ->assertSee('"per_page":'.config('monica.api_maximum_items_per_page'));
    }

    #[Test]
    public function it_refuses_a_page_size_that_is_not_a_number(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(ListLogs::class, ['per_page' => 'many'])
            ->assertHasErrors();
    }

    #[Test]
    public function it_writes_nothing_to_the_activity(): void
    {
        $user = User::factory()->create();
        Log::factory()->create(['account_id' => $user->account_id]);
        $before = Log::query()->count();

        EricaServer::actingAs($user)->tool(ListLogs::class)->assertOk();

        $this->assertDatabaseCount('logs', $before);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.logs', app(ListLogs::class)->mirrors());
    }
}
