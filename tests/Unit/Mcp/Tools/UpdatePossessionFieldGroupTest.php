<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionFieldGroup;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'name' => 'Warranty',
        ])->assertOk()->assertHasNoErrors()->assertSee('Warranty');

        $this->assertSame('Warranty', $group->refresh()->name);
    }

    #[Test]
    public function it_reports_a_group_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id, 'name' => 'Purchase']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'group' => $group->uuid,
            'name' => 'Warranty',
        ])->assertHasErrors();

        $this->assertSame('Purchase', $group->refresh()->name);
    }
}
