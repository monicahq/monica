<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_filed_under_one_category(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'name' => 'Second', 'position' => 2]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'name' => 'First', 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $elsewhere->id, 'name' => 'Elsewhere']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.0.attributes.life_event_category_id', $category->uuid);
    }

    #[Test]
    public function it_lists_the_types_of_a_category_that_are_no_longer_offered(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
    }

    #[Test]
    public function it_returns_not_found_for_a_category_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}/types")->assertNotFound();
    }
}
