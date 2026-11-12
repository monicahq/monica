<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionFieldGroup;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_group_and_leaves_its_fields_standing_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id]);
        $field = PossessionField::factory()->inGroup($group)->create();

        MonicaServer::actingAs($user)->tool(DeletePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertModelMissing($group);
        $this->assertNull($field->refresh()->possession_field_group_id);
    }

    #[Test]
    public function it_reports_a_group_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
        ])->assertHasErrors();

        $this->assertModelExists($group);
    }
}
