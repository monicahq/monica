<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ActivityTypes;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_inside_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $first = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        $second = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        $response = $this->actingAs($user)->put(route('settings.activityTypePosition.update', $second), ['position' => 1]);

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('settings.activityTypePosition.update', $type), ['position' => 'first'])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_answers_not_found_for_a_type_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $dwight->account_id, 'position' => 3]);

        $this->actingAs($michael)
            ->put(route('settings.activityTypePosition.update', $type), ['position' => 1])
            ->assertStatus(404);
    }
}
