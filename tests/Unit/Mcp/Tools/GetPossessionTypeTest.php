<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionType;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_possession_type_with_its_groups_and_fields(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop']);
        PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Serial number']);

        MonicaServer::actingAs($user)->tool(GetPossessionType::class, ['possession_type' => $type->uuid])
            ->assertOk()
            ->assertSee('Office equipment')
            ->assertSee('Purchase')
            ->assertSee('Shop')
            ->assertSee('Serial number');
    }

    #[Test]
    public function it_reports_a_possession_type_of_another_account_as_not_existing(): void
    {
        $type = PossessionType::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(GetPossessionType::class, ['possession_type' => $type->uuid])
            ->assertHasErrors();
    }
}
