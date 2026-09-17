<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ActivityTypes;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_category_and_selects_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.activityCategories.create'), [
            'name' => 'Music and choir',
        ]);

        $category = ActivityCategory::query()->where('name', 'Music and choir')->firstOrFail();

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('activity_categories', [
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
            ->post(route('settings.activityCategories.create'), ['name' => '  '])
            ->assertSessionHasErrors('name', null, 'newCategory');

        $this->assertDatabaseCount('activity_categories', 0);
    }

    #[Test]
    public function it_renames_a_category_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('settings.activityCategories.update', $category), [
            'name' => 'Me, myself and I',
        ]);

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $this->assertDatabaseHas('activity_categories', [
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
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.activityCategories.destroy', $category));

        $response->assertRedirect(route('settings.activityTypes.index'));
        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_category_that_still_holds_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        $response = $this->actingAs($user)->delete(route('settings.activityCategories.destroy', $category));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_category_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.activityCategories.destroy', $category));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($category);
    }

    #[Test]
    public function it_answers_not_found_for_a_category_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Beets']);

        $this->actingAs($michael)
            ->put(route('settings.activityCategories.update', $category), ['name' => 'Stolen'])
            ->assertStatus(404);

        $this->assertSame('Beets', $category->refresh()->name);
    }
}
