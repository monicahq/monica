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
    public function it_replaces_a_location_as_the_api_does(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse', 'emoji' => '📦', 'address' => '1725 Slough Avenue']);

        MonicaServer::actingAs($user)->tool(UpdateLocation::class, [
            'location' => $warehouse->uuid,
            'name' => 'Annex',
        ])->assertOk()->assertHasNoErrors()->assertSee('Annex');

        $this->assertDatabaseHas('locations', [
            'id' => $warehouse->id,
            'parent_id' => null,
            'name' => 'Annex',
            'emoji' => null,
            'address' => null,
        ]);
    }
}
