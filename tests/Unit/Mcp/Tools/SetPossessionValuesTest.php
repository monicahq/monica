<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\PossessionFieldKindEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetPossessionValues;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetPossessionValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_values_of_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $plate = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Plate', 'kind' => PossessionFieldKindEnum::Text]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionValues::class, [
            'vault' => $vault->uuid,
            'possession' => $sebring->uuid,
            'values' => [$plate->uuid => 'PA-1234'],
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('PA-1234');
    }

    #[Test]
    public function it_names_a_value_that_does_not_fit(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionValues::class, [
            'vault' => $vault->uuid,
            'possession' => $sebring->uuid,
            'values' => [$mileage->uuid => 'banana'],
        ])->assertHasErrors();
    }
}
