<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_one_category_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id, 'name' => 'Second', 'position' => 2]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id, 'name' => 'First', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.0.attributes.relationship_category_id', $category->uuid);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_leaves_out_the_types_of_another_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $other = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id, 'name' => 'Wanted']);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $other->id, 'name' => 'Unwanted']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Wanted');
    }

    #[Test]
    public function it_answers_an_empty_list_for_a_category_holding_nothing(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}/types");

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_returns_not_found_for_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $stranger->account_id]);
        RelationshipType::factory()->create(['account_id' => $stranger->account_id, 'relationship_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}/types")->assertNotFound();
    }
}
