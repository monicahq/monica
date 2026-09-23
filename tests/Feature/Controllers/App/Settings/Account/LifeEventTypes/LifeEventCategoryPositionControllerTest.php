<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\LifeEventTypes;

use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $response = $this->actingAs($user)->put(route('settings.lifeEventCategoryPosition.update', $second), [
            'position' => 1,
        ]);

        $response->assertRedirect(route('settings.lifeEventTypes.category', $second));
        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        $this->actingAs($user)
            ->put(route('settings.lifeEventCategoryPosition.update', $category), ['position' => 'first'])
            ->assertSessionHasErrors('position');
    }
}
