<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_life_event_type_inside_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $first = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 1]);
        $second = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$second->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 1);
        $this->assertDatabaseHas('life_event_types', ['id' => $second->id, 'position' => 1]);
        $this->assertDatabaseHas('life_event_types', ['id' => $first->id, 'position' => 2]);
    }

    #[Test]
    public function it_numbers_the_retired_types_among_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $retired = LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 1]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 1])->assertOk();

        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'position' => 1]);
        $this->assertDatabaseHas('life_event_types', ['id' => $retired->id, 'position' => 2]);
    }

    #[Test]
    public function it_places_a_type_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 99]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_leaves_the_types_of_another_category_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);
        $untouched = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $elsewhere->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 1])->assertOk();

        $this->assertDatabaseHas('life_event_types', ['id' => $untouched->id, 'position' => 1]);
    }

    #[Test]
    public function it_validates_the_position_it_is_sent(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 0]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_life_event_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 1])->assertNotFound();
    }
}
