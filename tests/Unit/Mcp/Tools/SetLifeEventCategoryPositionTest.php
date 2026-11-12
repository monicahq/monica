<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetLifeEventCategoryPosition;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLifeEventCategoryPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_life_event_category(): void
    {
        $user = User::factory()->create();
        $office = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Office', 'position' => 1]);
        $warehouse = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse', 'position' => 2]);
        $sales = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Sales', 'position' => 3]);

        EricaServer::actingAs($user)->tool(SetLifeEventCategoryPosition::class, [
            'life_event_category' => $sales->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(
            [$sales->uuid, $office->uuid, $warehouse->uuid],
            $user->account->lifeEventCategories()->orderBy('position')->pluck('uuid')->all(),
        );
    }
}
