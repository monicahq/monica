<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\ViewModels\Vaults\RelationshipTypeChoices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_retires_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $this->assertFalse($category->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_retired_category_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->inactive()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', true);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it_when_sent_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])->assertOk();
        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false);
    }

    #[Test]
    public function it_retires_a_supplied_category_like_any_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false)
            ->assertJsonPath('data.attributes.is_system', true);
    }

    #[Test]
    public function it_takes_a_retired_category_and_its_types_out_of_the_relationship_picker(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->assertCount(1, new RelationshipTypeChoices(accountId: $user->account_id)->groups());

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])->assertOk();

        $this->assertCount(0, new RelationshipTypeChoices(accountId: $user->account_id)->groups());
    }

    #[Test]
    public function it_leaves_the_types_of_a_retired_category_where_they_were(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.type_count', 1)
            ->assertJsonPath('data.attributes.position', 2);

        $this->assertTrue($type->refresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_state_that_is_not_a_boolean(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    #[Test]
    public function it_returns_not_found_for_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false])->assertNotFound();
        $this->assertTrue($category->refresh()->is_active);
    }
}
