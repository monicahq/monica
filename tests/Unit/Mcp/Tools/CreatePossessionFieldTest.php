<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_select_field_to_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionField::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'name' => 'Condition',
            'kind' => 'select',
            'options' => ['New', 'Used'],
        ])->assertOk()->assertSee('Condition');

        $this->assertDatabaseHas('possession_fields', [
            'possession_type_id' => $type->id,
            'possession_field_group_id' => $group->id,
            'name' => 'Condition',
            'kind' => 'select',
        ]);
    }

    #[Test]
    public function it_adds_a_standalone_field_when_no_group_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionField::class, [
            'possession_type' => $type->uuid,
            'name' => 'Serial number',
            'kind' => 'text',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('possession_fields', [
            'possession_type_id' => $type->id,
            'possession_field_group_id' => null,
            'name' => 'Serial number',
        ]);
    }

    #[Test]
    public function it_reports_a_group_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionField::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'name' => 'Shop',
            'kind' => 'text',
        ])->assertHasErrors();

        $this->assertDatabaseCount('possession_fields', 0);
    }

    #[Test]
    public function it_refuses_an_unknown_kind(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionField::class, [
            'possession_type' => $type->uuid,
            'name' => 'Shop',
            'kind' => 'hologram',
        ])->assertHasErrors();

        $this->assertDatabaseCount('possession_fields', 0);
    }
}
