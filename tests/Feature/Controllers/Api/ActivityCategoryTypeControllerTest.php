<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityCategoryTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_filed_under_one_category(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'name' => 'Second', 'position' => 2]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'name' => 'First', 'position' => 1]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $elsewhere->id, 'name' => 'Elsewhere']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/activity-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.0.attributes.activity_category_id', $category->uuid);
    }

    #[Test]
    public function it_lists_the_types_of_a_category_that_are_no_longer_offered(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->inactive()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/activity-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
    }

    #[Test]
    public function it_answers_an_empty_list_for_a_category_holding_nothing(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/activity-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_returns_not_found_for_a_category_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $other->account_id]);
        ActivityType::factory()->create(['account_id' => $other->account_id, 'activity_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/activity-categories/{$category->uuid}/types")->assertNotFound();
    }
}
