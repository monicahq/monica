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
    public function it_relays_the_refusal_of_the_api(): void
    {
        MonicaServer::actingAs(User::factory()->create())->tool(CreateLocation::class, [
            'name' => 'Warehouse',
            'parent_id' => 'the-annex',
        ])->assertHasErrors(['The parent id field must be a valid UUID.']);

        $this->assertDatabaseCount('locations', 0);
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
