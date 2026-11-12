<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\PossessionFieldKindEnum;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldValueControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_values_and_answers_them_typed_in_the_order_of_the_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number, 'position' => 2]);
        $convertible = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Convertible', 'kind' => PossessionFieldKindEnum::Boolean, 'position' => 1]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/values", [
            'values' => [$mileage->uuid => 120000, $convertible->uuid => true],
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.values.0.name', 'Convertible')
            ->assertJsonPath('data.attributes.values.0.value', true)
            ->assertJsonPath('data.attributes.values.1.name', 'Mileage')
            ->assertJsonPath('data.attributes.values.1.value', 120000);
    }

    #[Test]
    public function it_names_the_field_whose_value_does_not_fit(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $car->id, 'kind' => PossessionFieldKindEnum::Number]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/values", [
            'values' => [$mileage->uuid => 'banana'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["values.{$mileage->uuid}"]);
    }
}
