<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionConditions;

use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionConditionPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_possession_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $mint = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-conditions/{$mint->uuid}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Mint')
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-conditions/{$possessionCondition->uuid}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }
}
