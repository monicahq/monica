<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $first = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        $second = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2, 'name' => 'Power']);

        $this->actingAs($user)->put(route('settings.possessionFieldPosition.update', [$possessionType, $second]), ['position' => 1])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved')
            ->assertSessionHas('moved', "field-{$second->uuid}")
            ->assertSessionHas('direction', 'up');

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldPosition.update', [$possessionType, $field]), ['position' => 0])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldPosition.update', [$possessionType, $field]), ['position' => 1])
            ->assertNotFound();
    }
}
