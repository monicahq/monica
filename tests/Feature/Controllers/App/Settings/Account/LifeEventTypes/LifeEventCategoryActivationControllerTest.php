<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\LifeEventTypes;

use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_a_category_off(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('settings.lifeEventCategoryActivation.update', $category), [
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('settings.lifeEventTypes.category', $category));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('life_event_categories', ['id' => $category->id, 'is_active' => false]);
    }
}
