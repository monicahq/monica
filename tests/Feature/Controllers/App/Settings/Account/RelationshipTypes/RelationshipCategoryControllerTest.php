<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\RelationshipTypes;

use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.relationshipCategories.create'), ['name' => 'Neighbourhood'])
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('relationship_categories', [
            'account_id' => $user->account_id,
            'name' => 'Neighbourhood',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_category_with_no_name_and_reopens_the_window_that_asked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.relationshipCategories.create'), [])
            ->assertSessionHasErrors(['name'], errorBag: 'newCategory');

        $this->actingAs($user)->get(route('settings.relationshipTypes.index'))
            ->assertStatus(200)
            ->assertSee('Add a category');
    }

    #[Test]
    public function it_renames_a_category_the_user_added(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.relationshipCategories.update', $category), ['name' => 'Around the block'])
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertEquals('Around the block', $category->refresh()->name);
    }

    #[Test]
    public function it_deletes_an_empty_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->delete(route('settings.relationshipCategories.destroy', $category))
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_category_that_still_holds_a_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->actingAs($user)->delete(route('settings.relationshipCategories.destroy', $category))
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($category);
    }
}
