<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetPossessionFieldPosition;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetPossessionFieldPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_to_a_position(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $serial = PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Serial number', 'position' => 1]);
        $power = PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Power', 'position' => 2]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldPosition::class, [
            'possession_type' => $type->uuid,
            'field' => $power->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(1, $power->refresh()->position);
        $this->assertSame(2, $serial->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldPosition::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
            'position' => 0,
        ])->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_field_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionFieldPosition::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
            'position' => 1,
        ])->assertHasErrors();
    }
}
