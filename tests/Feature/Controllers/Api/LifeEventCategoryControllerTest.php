<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\CreateAccount;
use App\Enums\LifeEventCategoryEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryControllerTest extends TestCase
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
                'life_event_type_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_eight_groups_a_new_account_is_given(): void
    {
        Queue::fake(LogUserAction::class);

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
        )->execute();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-categories');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(8, 'data');
        $response->assertJsonPath('meta.total', 8);
        $response->assertJsonPath('data.0.type', 'life_event_category');
        $response->assertJsonPath('data.0.attributes.name', __('Relationships and family'));
        $response->assertJsonPath('data.0.attributes.position', 1);
        $response->assertJsonPath('data.0.attributes.is_system', true);
        $response->assertJsonPath('data.0.attributes.life_event_type_count', 9);
        $response->assertJsonPath('data.7.attributes.name', __('Other'));
    }

    #[Test]
    public function it_counts_the_types_filed_under_a_group(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);
        LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.life_event_type_count', 2);
    }

    #[Test]
    public function it_lists_the_groups_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Second', 'position' => 2]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'First', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-categories');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.name', 'First');
        $response->assertJsonPath('data.1.attributes.name', 'Second');
    }

    #[Test]
    public function it_lists_the_groups_that_are_no_longer_offered_among_the_rest(): void
    {
        $user = User::factory()->create();
        LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'position' => 1]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-categories');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
        $response->assertJsonPath('data.1.attributes.is_active', true);
    }

    #[Test]
    public function it_does_not_list_the_groups_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/life-event-categories');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_brings_a_page_size_outside_the_range_back_into_it(): void
    {
        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/life-event-categories?per_page=1000')->assertJsonPath('meta.per_page', 100);
        $this->json('GET', '/api/v1/life-event-categories?per_page=0')->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function it_shows_a_group(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $category->uuid);
        $response->assertJsonPath('data.attributes.name', 'Volunteering');
        $response->assertJsonPath('data.attributes.is_system', false);
    }

    #[Test]
    public function it_reads_the_words_the_user_wrote_whatever_language_is_asked_for(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create([
            'account_id' => $user->account_id,
            'name' => 'Pour moi',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Pour moi');
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_deleted_group(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $category->delete();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_adds_a_group_last_in_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/life-event-categories', ['name' => 'Volunteering']);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Volunteering');
        $response->assertJsonPath('data.attributes.position', 2);
        $response->assertJsonPath('data.attributes.is_system', false);
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.life_event_type_count', 0);
        $this->assertDatabaseHas('life_event_categories', [
            'account_id' => $user->account_id,
            'name' => 'Volunteering',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_name_made_only_of_spaces(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/life-event-categories', ['name' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_renames_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}", ['name' => 'Bénévolat']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Bénévolat');
        $response->assertJsonPath('data.id', $category->uuid);
    }

    #[Test]
    public function it_renames_a_supplied_group_and_keeps_it_marked_as_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system(LifeEventCategoryEnum::HealthAndWellbeing)->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}", ['name' => 'Santé']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Santé');
        $response->assertJsonPath('data.attributes.is_system', true);
        $response->assertJsonPath('data.attributes.position', 3);
        $this->assertEquals('health_and_wellbeing', $category->refresh()->key);
    }

    #[Test]
    public function it_reads_a_renamed_supplied_group_in_the_words_that_were_written(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system(LifeEventCategoryEnum::HealthAndWellbeing)->create([
            'account_id' => $user->account_id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}", ['name' => 'Santé'])->assertOk();

        $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Santé');
    }

    #[Test]
    public function it_deletes_a_group_the_account_added(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/life-event-categories/{$category->uuid}")->assertNoContent();
        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_supplied_group(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/life-event-categories/{$category->uuid}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['lifeEventCategory']);
        $this->assertNotSoftDeleted($category);
    }

    #[Test]
    public function it_returns_not_found_when_writing_to_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $stranger->account_id, 'name' => 'Volunteering']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}", ['name' => 'Bénévolat'])->assertNotFound();
        $this->json('DELETE', "/api/v1/life-event-categories/{$category->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($category);
        $this->assertEquals('Volunteering', $category->refresh()->name);
    }

    #[Test]
    public function it_returns_not_found_when_the_identifier_is_not_a_uuid(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/life-event-categories/{$category->id}")->assertNotFound();
    }
}
