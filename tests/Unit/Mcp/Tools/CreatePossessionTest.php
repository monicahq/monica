<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossession;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossession::class, [
            'vault' => $vault->uuid,
            'type_id' => $car->uuid,
            'name' => 'Chrysler Sebring',
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Chrysler Sebring');

        $this->assertDatabaseHas('possessions', ['vault_id' => $vault->id, 'name' => 'Chrysler Sebring', 'status' => 'owned']);
    }

    #[Test]
    public function it_records_how_it_was_acquired_and_its_tags(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        MonicaServer::actingAs($user)->tool(CreatePossession::class, [
            'vault' => $vault->uuid,
            'type_id' => PossessionType::factory()->create(['account_id' => $user->account_id])->uuid,
            'name' => 'Porcelain cat',
            'declared_value' => 120,
            'declared_value_currency' => 'usd',
            'tags' => ['fragile'],
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('fragile');

        $this->assertDatabaseHas('possessions', ['name' => 'Porcelain cat', 'declared_value' => '120.00', 'declared_value_currency' => 'usd']);
    }

    #[Test]
    public function it_reports_a_type_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossession::class, [
            'vault' => $vault->uuid,
            'type_id' => PossessionType::factory()->create()->uuid,
            'name' => 'Chrysler Sebring',
        ])->assertHasErrors();
    }
}
