<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionCondition;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_condition(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new']);

        MonicaServer::actingAs($user)->tool(GetPossessionCondition::class, ['possession_condition' => $possessionCondition->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Like new');
    }

    #[Test]
    public function it_reports_a_condition_of_another_account_as_not_existing(): void
    {
        $possessionCondition = PossessionCondition::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(GetPossessionCondition::class, ['possession_condition' => $possessionCondition->uuid])
            ->assertHasErrors();
    }
}
