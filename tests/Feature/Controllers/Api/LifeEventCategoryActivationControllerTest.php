<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_retires_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $this->assertFalse($category->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_retired_group_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', true);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it_when_sent_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false])->assertOk();
        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false);
    }

    #[Test]
    public function it_retires_a_supplied_group_like_any_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false)
            ->assertJsonPath('data.attributes.is_system', true);
    }

    #[Test]
    public function it_leaves_the_name_and_the_place_of_a_retired_group_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Volunteering',
            'position' => 3,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Volunteering')
            ->assertJsonPath('data.attributes.position', 3);
    }

    #[Test]
    public function it_refuses_a_state_that_is_not_a_boolean(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $stranger->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false])->assertNotFound();
        $this->assertTrue($category->refresh()->is_active);
    }
}
