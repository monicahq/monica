<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_group_to_the_front_of_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-categories/{$second->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 1);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_places_a_group_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/position", ['position' => 99])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_numbers_the_groups_from_one_again_counting_the_retired_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $retired = LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'position' => 5]);
        $moved = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 9]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$moved->uuid}/position", ['position' => 1])->assertOk();

        $this->assertSame(1, $moved->refresh()->position);
        $this->assertSame(2, $retired->refresh()->position);
    }

    #[Test]
    public function it_moves_a_supplied_group_like_any_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $supplied = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$supplied->uuid}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $stranger->account_id, 'position' => 3]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/position", ['position' => 1])->assertNotFound();
        $this->assertSame(3, $category->refresh()->position);
    }
}
