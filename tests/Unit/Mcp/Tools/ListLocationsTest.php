<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListLocations;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLocationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_one_page_of_the_locations_of_the_account(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->create(['name' => 'Stamford']);

        MonicaServer::actingAs($user)->tool(ListLocations::class, ['page' => 2, 'per_page' => 1])
            ->assertOk()
            ->assertSee('Scranton › Warehouse')
            ->assertDontSee('Stamford')
            ->assertSee('"total":2');
    }
}
