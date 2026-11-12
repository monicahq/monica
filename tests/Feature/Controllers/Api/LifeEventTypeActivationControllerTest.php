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

class LifeEventTypeActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_retires_a_life_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => false]);
    }

    #[Test]
    public function it_offers_a_retired_life_event_type_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", ['is_active' => true]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', true);
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => true]);
    }

    #[Test]
    public function it_keeps_the_place_a_retired_type_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_validates_the_state_it_is_sent(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['is_active']);
    }

    #[Test]
    public function it_returns_not_found_for_a_life_event_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", ['is_active' => false])->assertNotFound();
    }
}
