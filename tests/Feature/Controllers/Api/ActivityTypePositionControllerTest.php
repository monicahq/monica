<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_an_activity_type_inside_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $first = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        $second = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/activity-types/{$second->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 1);
        $this->assertDatabaseHas('activity_types', ['id' => $second->id, 'position' => 1]);
        $this->assertDatabaseHas('activity_types', ['id' => $first->id, 'position' => 2]);
    }

    #[Test]
    public function it_numbers_the_retired_types_among_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $retired = ActivityType::factory()->inactive()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/position", ['position' => 1])->assertOk();

        $this->assertDatabaseHas('activity_types', ['id' => $type->id, 'position' => 1]);
        $this->assertDatabaseHas('activity_types', ['id' => $retired->id, 'position' => 2]);
    }

    #[Test]
    public function it_places_a_type_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/position", ['position' => 99]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_leaves_the_types_of_another_category_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);
        $untouched = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $elsewhere->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/position", ['position' => 1])->assertOk();

        $this->assertDatabaseHas('activity_types', ['id' => $untouched->id, 'position' => 1]);
    }

    #[Test]
    public function it_validates_the_position_it_is_sent(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/position", ['position' => 0]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_an_activity_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $other->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $other->account_id, 'activity_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/activity-types/{$type->uuid}/position", ['position' => 1])->assertNotFound();
    }
}
