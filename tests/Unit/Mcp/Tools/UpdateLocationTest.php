<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse', 'emoji' => '📦', 'address' => '1725 Slough Avenue']);

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $warehouse->uuid,
            'name' => 'Annex',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('locations', [
            'id' => $warehouse->id,
            'parent_id' => $scranton->id,
            'name' => 'Annex',
            'emoji' => '📦',
            'address' => '1725 Slough Avenue',
        ]);
    }

    #[Test]
    public function it_moves_a_location_to_the_top_level_when_its_parent_is_cleared(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create(['emoji' => '📦']);

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $warehouse->uuid,
            'parent_id' => null,
            'emoji' => null,
        ])->assertOk()->assertHasNoErrors();

        $warehouse->refresh();
        $this->assertNull($warehouse->parent_id);
        $this->assertNull($warehouse->emoji);
    }

    #[Test]
    public function it_refuses_to_put_a_location_inside_one_it_holds(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create();

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $scranton->uuid,
            'parent_id' => $warehouse->uuid,
        ])->assertHasErrors();

        $this->assertNull($scranton->refresh()->parent_id);
    }
}
