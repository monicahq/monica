<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_location_inside_another(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);

        MonicaServer::actingAs($user)->tool(CreateLocation::class, [
            'name' => 'Warehouse',
            'parent_id' => $scranton->uuid,
            'emoji' => '📦',
        ])->assertOk()->assertSee('Scranton › Warehouse');

        $this->assertDatabaseHas('locations', [
            'account_id' => $user->account_id,
            'parent_id' => $scranton->id,
            'name' => 'Warehouse',
            'emoji' => '📦',
        ]);
    }

    #[Test]
    public function it_refuses_a_parent_of_another_account(): void
    {
        $stamford = Location::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(CreateLocation::class, [
            'name' => 'Warehouse',
            'parent_id' => $stamford->uuid,
        ])->assertHasErrors();

        $this->assertDatabaseCount('locations', 1);
    }
}
