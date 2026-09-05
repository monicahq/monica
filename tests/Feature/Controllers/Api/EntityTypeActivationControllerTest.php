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

class EntityTypeActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_records_being_filed_under_an_entity_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(4)->create([
            'account_id' => $user->account_id,
            'position' => 2,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", [
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $response->assertJsonPath('data.attributes.position', 2);
        $response->assertJsonPath('data.attributes.entity_count', 4);
        $this->assertFalse($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_lets_records_be_filed_under_an_entity_type_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", [
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.position', 3);
        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_confirms_the_state_an_entity_type_is_already_in_rather_than_inverting_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        foreach (range(1, 2) as $ignored) {
            $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", [
                'is_active' => false,
            ]);

            $response->assertOk();
            $response->assertJsonPath('data.attributes.is_active', false);
        }

        $this->assertFalse($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_validates_the_state_it_is_asked_for(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['is_active']);
        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_returns_not_found_for_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", [
            'is_active' => false,
        ]);

        $response->assertNotFound();
        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_caller_holding_no_credential(): void
    {
        $entityType = EntityType::factory()->create();

        $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", [
            'is_active' => false,
        ])->assertUnauthorized();
    }
}
