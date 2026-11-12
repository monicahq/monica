<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossession;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_possession_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);

        MonicaServer::actingAs($user)->tool(GetPossession::class, ['vault' => $vault->uuid, 'possession' => $sebring->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Chrysler Sebring');
    }

    #[Test]
    public function it_reports_a_possession_of_another_vault_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        MonicaServer::actingAs($user)->tool(GetPossession::class, ['vault' => $vault->uuid, 'possession' => $sebring->uuid])
            ->assertHasErrors();
    }
}
