<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\LifeEventTypes;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_inside_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $first = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 1]);
        $second = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

        $response = $this->actingAs($user)->put(route('settings.lifeEventTypePosition.update', $second), ['position' => 1]);

        $response->assertRedirect(route('settings.lifeEventTypes.category', $category));
        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('settings.lifeEventTypePosition.update', $type), ['position' => 'first'])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_answers_not_found_for_a_type_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $dwight->account_id, 'position' => 3]);

        $this->actingAs($michael)
            ->put(route('settings.lifeEventTypePosition.update', $type), ['position' => 1])
            ->assertStatus(404);
    }
}
