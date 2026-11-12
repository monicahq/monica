<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_featured_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_refuses_a_possession_that_is_not_featured(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/position", ['position' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }
}
