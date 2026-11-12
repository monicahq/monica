<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_to_the_front_of_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $first = RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id, 'name' => 'First', 'position' => 1]);
        $second = RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id, 'name' => 'Second', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$second->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 1);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $stranger->account_id, 'position' => 3]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/position", ['position' => 1])->assertNotFound();
        $this->assertSame(3, $type->refresh()->position);
    }
}
