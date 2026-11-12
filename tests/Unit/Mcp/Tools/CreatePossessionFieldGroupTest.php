<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_group_to_a_possession_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'name' => 'Purchase',
        ])->assertOk()->assertSee('Purchase');

        $this->assertDatabaseHas('possession_field_groups', [
            'possession_type_id' => $type->id,
            'name' => 'Purchase',
        ]);
    }

    #[Test]
    public function it_reports_a_possession_type_of_another_account_as_not_existing(): void
    {
        $type = PossessionType::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(CreatePossessionFieldGroup::class, [
            'possession_type' => $type->uuid,
            'name' => 'Purchase',
        ])->assertHasErrors();

        $this->assertDatabaseCount('possession_field_groups', 0);
    }
}
