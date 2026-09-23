<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'life_event_category_id',
                'name',
                'description',
                'icon',
                'colour',
                'position',
                'is_system',
                'is_active',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_life_event_types_of_the_account(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Dundies',
            'position' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-types');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'life_event_type');
        $response->assertJsonPath('data.0.attributes.name', 'Dundies');
        $response->assertJsonPath('data.0.attributes.life_event_category_id', $category->uuid);
    }

    #[Test]
    public function it_lists_the_types_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        $second = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $first = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $second->id, 'name' => 'Third', 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $first->id, 'name' => 'Second', 'position' => 2]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $first->id, 'name' => 'First', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-types');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
        $response->assertJsonPath('data.2.attributes.name', 'Third');
    }

    #[Test]
    public function it_lists_the_types_that_are_no_longer_offered(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Retired',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-types');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
    }

    #[Test]
    public function it_does_not_list_life_event_types_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-types');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_a_life_event_type(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Board game night',
            'description' => 'An evening around a table.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/life-event-types/{$type->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $type->uuid);
        $response->assertJsonPath('data.attributes.name', 'Board game night');
        $response->assertJsonPath('data.attributes.description', 'An evening around a table.');
        $response->assertJsonPath('data.links.self', route('api.v1.lifeEventTypes.show', $type->uuid));
    }

    #[Test]
    public function it_returns_not_found_for_a_life_event_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-types/{$type->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_path_that_is_not_a_uuid(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/life-event-types/1')->assertNotFound();
    }

    #[Test]
    public function it_creates_a_life_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/life-event-types', [
            'life_event_category_id' => $category->uuid,
            'name' => 'Board game night',
            'description' => 'An evening around a table.',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Board game night');
        $response->assertJsonPath('data.attributes.is_system', false);
        $response->assertJsonPath('data.attributes.is_active', true);
        $this->assertDatabaseHas('life_event_types', [
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Board game night',
        ]);
    }

    #[Test]
    public function it_places_a_created_type_last_in_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/life-event-types', [
            'life_event_category_id' => $category->uuid,
            'name' => 'Board game night',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_validates_the_name_when_creating_a_life_event_type(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/life-event-types', [
            'life_event_category_id' => $category->uuid,
            'name' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_refuses_a_glyph_the_product_does_not_ship(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/life-event-types', [
            'life_event_category_id' => $category->uuid,
            'name' => 'Board game night',
            'icon' => 'not-a-glyph',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['icon']);
    }

    #[Test]
    public function it_returns_not_found_when_creating_under_a_category_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/life-event-types', [
            'life_event_category_id' => $category->uuid,
            'name' => 'Board game night',
        ])->assertNotFound();
    }

    #[Test]
    public function it_updates_a_life_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Board game night',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}", [
            'life_event_category_id' => $category->uuid,
            'name' => 'Games evening',
            'description' => 'Cardboard and dice.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Games evening');
        $response->assertJsonPath('data.attributes.description', 'Cardboard and dice.');
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'name' => 'Games evening']);
    }

    #[Test]
    public function it_places_a_type_last_when_it_moves_to_another_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $from = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $to = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $to->id, 'position' => 1]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $from->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}", [
            'life_event_category_id' => $to->uuid,
            'name' => $type->name,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.life_event_category_id', $to->uuid);
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_updates_a_type_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->supplied()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}", [
            'life_event_category_id' => $category->uuid,
            'name' => 'Ring up',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Ring up');
        $response->assertJsonPath('data.attributes.is_system', true);
    }

    #[Test]
    public function it_returns_not_found_when_updating_a_life_event_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}", [
            'life_event_category_id' => $category->uuid,
            'name' => 'Stolen',
        ])->assertNotFound();
    }

    #[Test]
    public function it_deletes_a_life_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/life-event-types/{$type->uuid}")->assertNoContent();

        $this->assertSoftDeleted($type);
    }

    #[Test]
    public function it_refuses_to_delete_a_type_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->supplied()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/life-event-types/{$type->uuid}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['lifeEventType']);
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_a_life_event_type_from_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $other->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $other->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/life-event-types/{$type->uuid}")->assertNotFound();
    }
}
