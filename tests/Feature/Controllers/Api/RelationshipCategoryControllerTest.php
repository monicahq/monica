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

class RelationshipCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'position',
                'is_system',
                'is_active',
                'type_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_categories_of_the_account_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Second', 'position' => 2]);
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'First', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-categories');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.0.type', 'relationship_category');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_lists_the_categories_that_are_no_longer_offered_among_the_rest(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Retired', 'position' => 1]);
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Offered', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-categories');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
        $response->assertJsonPath('data.1.attributes.is_active', true);
    }

    #[Test]
    public function it_counts_the_types_a_category_holds(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->count(3)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-categories');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.type_count', 3);
    }

    #[Test]
    public function it_does_not_list_the_categories_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/relationship-categories');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_a_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Neighbourhood']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $category->uuid);
        $response->assertJsonPath('data.attributes.name', 'Neighbourhood');
    }

    #[Test]
    public function it_returns_not_found_for_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_adds_a_category_last_in_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/relationship-categories', ['name' => 'Neighbourhood']);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Neighbourhood');
        $response->assertJsonPath('data.attributes.position', 2);
        $response->assertJsonPath('data.attributes.is_system', false);
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.type_count', 0);
        $this->assertDatabaseHas('relationship_categories', [
            'account_id' => $user->account_id,
            'name' => 'Neighbourhood',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_category_name_made_only_of_spaces(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/relationship-categories', ['name' => '   ']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_renames_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Neighbourhood']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}", ['name' => 'Voisinage']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Voisinage');
        $response->assertJsonPath('data.id', $category->uuid);
    }

    #[Test]
    public function it_refuses_to_rename_a_supplied_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}", ['name' => 'Famille']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
        $this->assertNull($category->refresh()->getRawOriginal('name'));
    }

    #[Test]
    public function it_deletes_a_category_holding_no_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/relationship-categories/{$category->uuid}")->assertNoContent();
        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_returns_not_found_when_writing_to_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}", ['name' => 'Voisinage'])->assertNotFound();
        $this->json('DELETE', "/api/v1/relationship-categories/{$category->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($category);
    }
}
