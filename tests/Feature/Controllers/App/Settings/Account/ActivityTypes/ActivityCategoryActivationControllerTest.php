<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ActivityTypes;

use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityCategoryActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_a_category_off(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('settings.activityCategoryActivation.update', $category), [
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('activity_categories', ['id' => $category->id, 'is_active' => false]);
    }

    #[Test]
    public function it_switches_a_supplied_category_back_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->system()->inactive()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.activityCategoryActivation.update', $category), [
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('activity_categories', ['id' => $category->id, 'is_active' => true]);
    }

    #[Test]
    public function it_answers_not_found_for_a_category_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)
            ->put(route('settings.activityCategoryActivation.update', $category), ['is_active' => 0])
            ->assertStatus(404);
    }
}
