<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionTypes;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_possession_types_of_the_account_with_their_fields(): void
    {
        $user = User::factory()->create();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Licence plate']);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Appliance']);
        PossessionType::factory()->create(['name' => 'Office equipment']);

        MonicaServer::actingAs($user)->tool(ListPossessionTypes::class)
            ->assertOk()
            ->assertSee('Vehicle')
            ->assertSee('Appliance')
            ->assertSee('Licence plate')
            ->assertSee($vehicle->uuid)
            ->assertDontSee('Office equipment');
    }
}
