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

class EntityTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'slug',
                'name_singular',
                'name_plural',
                'description',
                'icon',
                'colour',
                'position',
                'is_active',
                'entity_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_entity_types_of_the_account_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Wine bottles', 'position' => 2]);
        EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/entity-types');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name_plural', 'Animals');
        $response->assertJsonPath('data.1.attributes.name_plural', 'Wine bottles');
    }

    #[Test]
    public function it_lists_the_types_nobody_may_file_records_under_alongside_the_rest(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->inactive()->create(['account_id' => $user->account_id, 'name_plural' => 'Recipes', 'position' => 1]);
        EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/entity-types');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name_plural', 'Recipes');
        $response->assertJsonPath('data.0.attributes.is_active', false);
        $response->assertJsonPath('data.1.attributes.is_active', true);
    }

    #[Test]
    public function it_does_not_list_the_entity_types_of_another_account(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create(['name_plural' => 'Companies']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/entity-types');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_an_entity_type(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(4)->create([
            'account_id' => $user->account_id,
            'slug' => 'animal',
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'description' => 'Every creature the family keeps.',
            'icon' => 'profile',
            'colour' => '#4f8a49',
            'position' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/entity-types/{$entityType->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'entity_type');
        $response->assertJsonPath('data.id', $entityType->uuid);
        $response->assertJsonPath('data.attributes.slug', 'animal');
        $response->assertJsonPath('data.attributes.name_singular', 'Animal');
        $response->assertJsonPath('data.attributes.name_plural', 'Animals');
        $response->assertJsonPath('data.attributes.description', 'Every creature the family keeps.');
        $response->assertJsonPath('data.attributes.icon', 'profile');
        $response->assertJsonPath('data.attributes.colour', '#4f8a49');
        $response->assertJsonPath('data.attributes.position', 1);
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.entity_count', 4);
        $response->assertJsonPath('data.links.self', config('app.url')."/api/v1/entity-types/{$entityType->uuid}");
    }

    #[Test]
    public function it_returns_not_found_for_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/entity-types/{$entityType->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_creates_an_entity_type_and_derives_its_slug(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/entity-types', [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'description' => 'What is in the cellar.',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.slug', 'wine-bottle');
        $response->assertJsonPath('data.attributes.name_plural', 'Wine bottles');
        $response->assertJsonPath('data.attributes.description', 'What is in the cellar.');
        $response->assertJsonPath('data.attributes.icon', EntityType::DEFAULT_ICON);
        $response->assertJsonPath('data.attributes.colour', EntityType::DEFAULT_COLOUR);
        $response->assertJsonPath('data.attributes.position', 2);
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.entity_count', 0);

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'slug' => 'wine-bottle',
            'name_singular' => 'Wine bottle',
            'position' => 2,
        ]);
    }

    #[Test]
    public function it_validates_the_entity_type_it_is_asked_to_create(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/entity-types', [
            'name_plural' => 'Animals',
            'icon' => 'sparkle',
            'colour' => '#ff0000',
            'slug' => 'Wine Bottles',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name_singular', 'icon', 'colour', 'slug']);
    }

    #[Test]
    public function it_refuses_a_slug_another_type_of_the_account_already_holds(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animal']);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/entity-types', [
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'slug' => 'animal',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['slug']);
    }

    #[Test]
    public function it_updates_the_wording_of_an_entity_type_and_leaves_everything_else_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(4)->create([
            'account_id' => $user->account_id,
            'slug' => 'animal',
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'position' => 3,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}", [
            'name_singular' => 'Pet',
            'name_plural' => 'Pets',
            'description' => null,
            'icon' => 'device',
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name_singular', 'Pet');
        $response->assertJsonPath('data.attributes.name_plural', 'Pets');
        $response->assertJsonPath('data.attributes.description', null);
        $response->assertJsonPath('data.attributes.icon', 'device');
        $response->assertJsonPath('data.attributes.colour', '#9a3f2b');
        $response->assertJsonPath('data.attributes.slug', 'animal');
        $response->assertJsonPath('data.attributes.position', 3);
        $response->assertJsonPath('data.attributes.is_active', false);
        $response->assertJsonPath('data.attributes.entity_count', 4);
    }

    #[Test]
    public function it_refuses_an_update_that_leaves_a_field_out_rather_than_erasing_it(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'description' => 'Every creature the family keeps.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}", [
            'name_singular' => 'Pet',
            'name_plural' => 'Pets',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['description', 'icon', 'colour']);
        $this->assertSame('Every creature the family keeps.', $entityType->refresh()->description);
    }

    #[Test]
    public function it_returns_not_found_when_updating_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['name_singular' => 'Animal']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}", [
            'name_singular' => 'Pet',
            'name_plural' => 'Pets',
            'description' => null,
            'icon' => 'profile',
            'colour' => '#4f8a49',
        ]);

        $response->assertNotFound();
        $this->assertSame('Animal', $entityType->refresh()->name_singular);
    }

    #[Test]
    public function it_deletes_an_entity_type_that_holds_no_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/entity-types/{$entityType->uuid}")->assertNoContent();

        $this->assertSoftDeleted($entityType);
        $this->json('GET', "/api/v1/entity-types/{$entityType->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_refuses_to_delete_an_entity_type_that_still_holds_records(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(7)->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/entity-types/{$entityType->uuid}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['entityType']);
        $this->assertNotSoftDeleted($entityType);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/entity-types/{$entityType->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($entityType);
    }
}
