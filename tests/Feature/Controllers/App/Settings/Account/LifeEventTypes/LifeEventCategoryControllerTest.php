<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\LifeEventTypes;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_category_and_selects_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.lifeEventCategories.create'), [
            'name' => 'Music and choir',
        ]);

        $category = LifeEventCategory::query()->where('name', 'Music and choir')->firstOrFail();

        $response->assertRedirect(route('settings.lifeEventTypes.category', $category));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('life_event_categories', [
            'account_id' => $user->account_id,
            'name' => 'Music and choir',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_category_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.lifeEventCategories.create'), ['name' => '  '])
            ->assertSessionHasErrors('name', null, 'newCategory');

        $this->assertDatabaseCount('life_event_categories', 0);
    }

    #[Test]
    public function it_renames_a_category_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('settings.lifeEventCategories.update', $category), [
            'name' => 'Me, myself and I',
        ]);

        $response->assertRedirect(route('settings.lifeEventTypes.category', $category));
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $category->id,
            'name' => 'Me, myself and I',
            'is_system' => true,
        ]);
    }

    #[Test]
    public function it_deletes_a_category_that_holds_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.lifeEventCategories.destroy', $category));

        $response->assertRedirect(route('settings.lifeEventTypes.index'));
        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_category_that_still_holds_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        $response = $this->actingAs($user)->delete(route('settings.lifeEventCategories.destroy', $category));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($category);
    }
}
