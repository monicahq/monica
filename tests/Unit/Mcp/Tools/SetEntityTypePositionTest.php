<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetEntityTypePosition;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetEntityTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_an_entity_type(): void
    {
        $user = User::factory()->create();
        $paper = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $printer = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $dundie = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        MonicaServer::actingAs($user)->tool(SetEntityTypePosition::class, [
            'entity_type' => $dundie->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(
            [$dundie->uuid, $paper->uuid, $printer->uuid],
            $user->account->entityTypes()->orderBy('position')->pluck('uuid')->all(),
        );
    }
}
