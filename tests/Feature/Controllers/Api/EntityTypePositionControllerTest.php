<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_an_entity_type_to_the_front_and_renumbers_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $recipes = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $bottles = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$bottles->uuid}/position", ['position' => 1]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 1);
        $this->assertSame(1, $bottles->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
        $this->assertSame(3, $recipes->refresh()->position);
    }

    #[Test]
    public function it_validates_the_position_it_is_asked_for(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/position", ['position' => 0]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['position']);
        $this->assertSame(1, $entityType->refresh()->position);
    }

    #[Test]
    public function it_returns_not_found_for_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['position' => 4]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/position", ['position' => 1]);

        $response->assertNotFound();
        $this->assertSame(4, $entityType->refresh()->position);
    }
}
