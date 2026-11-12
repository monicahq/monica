<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetPossessionFieldGroupPosition;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetPossessionFieldGroupPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_group_to_a_position(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase', 'position' => 1]);
        $technical = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Technical details', 'position' => 2]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldGroupPosition::class, [
            'possession_type' => $type->uuid,
            'group' => $technical->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(1, $technical->refresh()->position);
        $this->assertSame(2, $purchase->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldGroupPosition::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'position' => 0,
        ])->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_group_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldGroupPosition::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'position' => 1,
        ])->assertHasErrors();
    }
}
