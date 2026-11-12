<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionConditions;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionConditionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_conditions_of_the_account(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint']);
        PossessionCondition::factory()->create(['name' => 'Water damaged']);

        MonicaServer::actingAs($user)->tool(ListPossessionConditions::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Mint')
            ->assertDontSee('Water damaged');
    }
}
