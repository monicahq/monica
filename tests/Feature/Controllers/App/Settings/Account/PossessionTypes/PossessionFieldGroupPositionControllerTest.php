<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldGroupPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $first = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        $second = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2, 'name' => 'Warranty']);

        $this->actingAs($user)->put(route('settings.possessionFieldGroupPosition.update', [$possessionType, $second]), ['position' => 1])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved')
            ->assertSessionHas('direction', 'up');

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldGroupPosition.update', [$possessionType, $group]), ['position' => 0])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldGroupPosition.update', [$possessionType, $group]), ['position' => 1])
            ->assertNotFound();
    }
}
