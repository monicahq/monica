<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ExportPossessionType;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_file_of_a_possession_type(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop']);

        MonicaServer::actingAs($user)->tool(ExportPossessionType::class, ['possession_type' => $type->uuid])
            ->assertOk()
            ->assertSee('possession_type')
            ->assertSee('format_version')
            ->assertSee('Office equipment')
            ->assertSee('Purchase')
            ->assertSee('Shop');
    }

    #[Test]
    public function it_reports_a_possession_type_of_another_account_as_not_existing(): void
    {
        $type = PossessionType::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(ExportPossessionType::class, ['possession_type' => $type->uuid])
            ->assertHasErrors();
    }
}
