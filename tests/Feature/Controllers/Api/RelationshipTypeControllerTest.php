<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'relationship_category_id',
                'name',
                'role_a',
                'role_b',
                'is_symmetric',
                'system_behavior',
                'position',
                'is_system',
                'is_active',
                'relationship_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_types_of_the_account_by_category_then_by_position(): void
    {
        $user = User::factory()->create();
        $second = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $second->id, 'name' => 'Third', 'position' => 1]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $first->id, 'name' => 'Second', 'position' => 2]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $first->id, 'name' => 'First', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-types');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.2.attributes.name', 'Third');
        $response->assertJsonPath('data.0.type', 'relationship_type');
        $response->assertJsonPath('data.0.attributes.relationship_category_id', $first->uuid);
    }

    #[Test]
    public function it_lists_the_types_that_are_no_longer_offered_among_the_rest(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->inactive()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-types');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
    }

    #[Test]
    public function it_counts_the_relationships_written_with_a_type(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id]);

        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
            'contact_b_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/relationship-types/{$type->uuid}")->assertJsonPath('data.attributes.relationship_count', 1);
    }

    #[Test]
    public function it_does_not_list_the_types_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        RelationshipType::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-types');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_shows_a_type(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/relationship-types/{$type->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $type->uuid);
        $response->assertJsonPath('data.attributes.role_a', 'Godparent');
        $response->assertJsonPath('data.attributes.role_b', 'Godchild');
        $response->assertJsonPath('data.attributes.is_symmetric', false);
        $response->assertJsonPath('data.attributes.system_behavior', null);
    }

    #[Test]
    public function it_reads_the_words_the_user_wrote_whatever_language_is_asked_for(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id, 'name' => 'Voisin']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/relationship-types/{$type->uuid}")->assertJsonPath('data.attributes.name', 'Voisin');
    }

    #[Test]
    public function it_returns_not_found_for_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/relationship-types/{$type->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_adds_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/relationship-types', [
            'relationship_category_id' => $category->uuid,
            'name' => 'Godparent / Godchild',
            'role_a' => 'Godparent',
            'role_b' => 'Godchild',
            'is_symmetric' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Godparent / Godchild');
        $response->assertJsonPath('data.attributes.is_symmetric', false);
        $response->assertJsonPath('data.attributes.is_system', false);
        $response->assertJsonPath('data.attributes.system_behavior', null);
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.relationship_count', 0);
        $this->assertDatabaseHas('relationship_types', [
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Godparent / Godchild',
            'role_b' => 'Godchild',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_directed_type_without_a_role_on_each_side(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/relationship-types', [
            'relationship_category_id' => $category->uuid,
            'name' => 'Godparent / Godchild',
            'role_a' => 'Godparent',
            'is_symmetric' => false,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['role_b']);
    }

    #[Test]
    public function it_rewords_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $other = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'position' => 3,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", [
            'relationship_category_id' => $other->uuid,
            'name' => 'Marraine / Filleul',
            'role_a' => 'Marraine',
            'role_b' => 'Filleul',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Marraine / Filleul');
        $response->assertJsonPath('data.attributes.role_a', 'Marraine');
        $response->assertJsonPath('data.attributes.relationship_category_id', $other->uuid);
        $response->assertJsonPath('data.attributes.is_symmetric', false);
        $response->assertJsonPath('data.attributes.position', 3);
        $response->assertJsonPath('data.id', $type->uuid);
    }

    #[Test]
    public function it_refuses_to_change_the_direction_of_a_type_relationships_use(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);
        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
            'contact_b_id' => Contact::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", [
            'relationship_category_id' => $category->uuid,
            'name' => 'Godparent / Godchild',
            'role_a' => 'Godparent',
            'is_symmetric' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['is_symmetric']);
    }

    #[Test]
    public function it_deletes_a_type_no_relationship_uses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/relationship-types/{$type->uuid}")->assertNoContent();
        $this->assertSoftDeleted($type);
    }

    #[Test]
    public function it_returns_not_found_when_writing_to_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", [
            'relationship_category_id' => $category->uuid,
            'name' => 'Voisin',
            'role_a' => 'Voisin',
        ])->assertNotFound();

        $this->json('DELETE', "/api/v1/relationship-types/{$type->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($type);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Boss / Employee',
            'role_a' => 'Boss',
            'role_b' => 'Employee',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", [
            'name' => 'Regional manager / Salesman',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Regional manager / Salesman');
        $response->assertJsonPath('data.attributes.role_a', 'Boss');
        $response->assertJsonPath('data.attributes.role_b', 'Employee');
        $response->assertJsonPath('data.attributes.relationship_category_id', $category->uuid);
        $response->assertJsonPath('data.attributes.is_symmetric', false);
    }

    #[Test]
    public function it_refuses_a_role_sent_as_null(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", ['role_a' => null, 'role_b' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_a', 'role_b']);
    }
}
