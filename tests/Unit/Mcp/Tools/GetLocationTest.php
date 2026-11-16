<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_location_with_the_address_it_falls_back_on(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton', 'address' => '1725 Slough Avenue']);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);

        MonicaServer::actingAs($user)->tool(GetLocation::class, ['location' => $warehouse->uuid])
            ->assertOk()
            ->assertSee('{"type":"location"')
            ->assertSee('Scranton › Warehouse')
            ->assertSee('1725 Slough Avenue')
            ->assertSee($scranton->uuid);
    }

    #[Test]
    public function it_reports_a_location_of_another_account_as_not_existing(): void
    {
        $stamford = Location::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(GetLocation::class, ['location' => $stamford->uuid])
            ->assertHasErrors();
    }
}
