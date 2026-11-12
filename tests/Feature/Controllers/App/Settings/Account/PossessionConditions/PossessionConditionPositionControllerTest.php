<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionConditions;

use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionConditionPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_condition_and_says_where_it_landed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $mint = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 2]);

        $this->actingAs($user)
            ->put(route('settings.possessionConditionPosition.update', $mint), ['position' => 1])
            ->assertRedirect(route('settings.possessionConditions.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('moved', $mint->uuid)
            ->assertSessionHas('direction', 'up');

        $this->assertEquals(1, $mint->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('settings.possessionConditionPosition.update', $possessionCondition), ['position' => 0])
            ->assertSessionHasErrors('position');
    }
}
