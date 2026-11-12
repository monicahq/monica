<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_location_holding_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $annex = Location::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeleteLocation::class, ['location' => $annex->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($annex);
    }

    #[Test]
    public function it_refuses_a_location_holding_another(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        Location::factory()->inside($scranton)->create();

        MonicaServer::actingAs($user)->tool(DeleteLocation::class, ['location' => $scranton->uuid])
            ->assertHasErrors();

        $this->assertModelExists($scranton);
    }
}
