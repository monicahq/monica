<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateLocation;
use App\Mcp\Tools\CreatePossessionMaintenanceRecord;
use App\Mcp\Tools\GetLocation;
use App\Mcp\Tools\ListLogs;
use App\Mcp\Tools\SearchRecords;
use App\Mcp\Tools\UpdateLocation;
use App\Models\Location;
use App\Models\Log;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Mcp\Servers\ServerWithFixtureTools;
use Tests\Fixtures\Mcp\Tools\DeleteLocationAnsweringRaw;
use Tests\TestCase;

class MirrorsAnApiCapabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_passes_the_input_on_to_the_endpoint(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $warehouse->uuid,
            'name' => 'Annex',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('locations', ['id' => $warehouse->id, 'name' => 'Annex']);
    }

    #[Test]
    public function it_returns_a_single_record_without_the_data_wrapper(): void
    {
        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);

        MonicaServer::actingAs($user)->tool(GetLocation::class, ['location' => $warehouse->uuid])
            ->assertOk()
            ->assertSee('"name":"Warehouse"')
            ->assertDontSee('"data":');
    }

    #[Test]
    public function it_relays_a_refusal_of_the_endpoint_word_for_word(): void
    {
        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $warehouse->uuid,
            'name' => str_repeat('a', 256),
        ])->assertHasErrors([__('validation.max.string', ['attribute' => 'name', 'max' => 255])]);
    }

    #[Test]
    public function it_comes_back_empty_when_the_endpoint_answers_without_a_body(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);

        ServerWithFixtureTools::actingAs($user)->tool(DeleteLocationAnsweringRaw::class, ['location' => $warehouse->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($warehouse);
    }

    #[Test]
    public function it_reports_a_record_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $elsewhere = Location::factory()->create();

        MonicaServer::actingAs($user)->tool(GetLocation::class, ['location' => $elsewhere->uuid])
            ->assertHasErrors([__('That location does not exist.')]);
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        Log::factory()->count(3)->create(['account_id' => $user->account_id, 'user_id' => $user->id]);

        MonicaServer::actingAs($user)->tool(ListLogs::class, ['page' => 2, 'per_page' => 2])
            ->assertOk()
            ->assertSee('"page":2');
    }

    #[Test]
    public function it_reports_a_record_named_in_the_body_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionMaintenanceRecord::class, [
            'vault' => $vault->uuid,
            'possession' => $possession->uuid,
            'maintenance_type' => 'servicing',
            'title' => 'New toner for the Dunder Mifflin copier',
            'performed_on' => now()->toDateString(),
            'condition_before_id' => (string) Str::uuid(),
        ])->assertHasErrors([__('That record does not exist.')]);
    }

    #[Test]
    public function it_reports_an_error_the_endpoint_answers_without_throwing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SearchRecords::class, ['vault' => $vault->uuid, 'query' => 'Schrute'])
            ->assertHasErrors();
    }

    #[Test]
    public function it_reads_blank_values_as_the_api_does(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateLocation::class, [
            'name' => 'Warehouse',
            'parent_id' => '',
            'address' => '   ',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('locations', [
            'account_id' => $user->account_id,
            'parent_id' => null,
            'name' => 'Warehouse',
            'address' => null,
        ]);
    }
}
