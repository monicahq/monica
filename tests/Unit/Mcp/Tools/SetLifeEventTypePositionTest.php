<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetLifeEventTypePosition;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLifeEventTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_life_event_type_within_its_category(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $attributes = ['account_id' => $user->account_id, 'life_event_category_id' => $category->id];
        $dundie = LifeEventType::factory()->create([...$attributes, 'name' => 'Won a Dundie', 'position' => 1]);
        $committee = LifeEventType::factory()->create([...$attributes, 'name' => 'Joined a committee', 'position' => 2]);
        $promotion = LifeEventType::factory()->create([...$attributes, 'name' => 'Got promoted', 'position' => 3]);

        MonicaServer::actingAs($user)->tool(SetLifeEventTypePosition::class, [
            'life_event_type' => $promotion->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(
            [$promotion->uuid, $dundie->uuid, $committee->uuid],
            $category->lifeEventTypes()->orderBy('position')->pluck('uuid')->all(),
        );
    }
}
