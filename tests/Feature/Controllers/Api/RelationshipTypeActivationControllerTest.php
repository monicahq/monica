<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_retires_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $this->assertFalse($type->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_retired_type_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->inactive()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => true]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', true);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it_when_sent_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false])->assertOk();
        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false);
    }

    #[Test]
    public function it_retires_a_supplied_type_like_any_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->system()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $response->assertJsonPath('data.attributes.is_system', true);
    }

    #[Test]
    public function it_leaves_the_wording_and_the_place_of_a_retired_type_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Godparent / Godchild');
        $response->assertJsonPath('data.attributes.role_a', 'Godparent');
        $response->assertJsonPath('data.attributes.position', 3);
    }

    #[Test]
    public function it_leaves_the_relationships_written_with_a_retired_type_readable(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
            'contact_b_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.relationship_count', 1);

        $this->assertModelExists($relationship);
    }

    #[Test]
    public function it_refuses_a_state_that_is_not_a_boolean(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false])->assertNotFound();
        $this->assertTrue($type->refresh()->is_active);
    }
}
