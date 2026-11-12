<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetPossessionConditionPosition;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetPossessionConditionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $new = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $mint = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 2]);

        MonicaServer::actingAs($user)->tool(SetPossessionConditionPosition::class, [
            'possession_condition' => $mint->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors()->assertSee('Mint');

        $this->assertEquals(1, $mint->refresh()->position);
        $this->assertEquals(2, $new->refresh()->position);
    }

    #[Test]
    public function it_reports_a_condition_of_another_account_as_not_existing(): void
    {
        $possessionCondition = PossessionCondition::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(SetPossessionConditionPosition::class, [
            'possession_condition' => $possessionCondition->uuid,
            'position' => 1,
        ])->assertHasErrors();
    }
}
