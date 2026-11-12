<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\RelationshipTypes;

use App\Enums\RelationshipTypeEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_the_account_under_their_categories(): void
    {
        $user = User::factory()->create();
        $family = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family']);
        RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $family->id,
        ]);

        $this->actingAs($user)->get(route('settings.relationshipTypes.index'))
            ->assertStatus(200)
            ->assertSee('Family')
            ->assertSee('Godchild');
    }

    #[Test]
    public function it_adds_a_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.relationshipTypes.create'), [
            'name' => 'Godparent / Godchild',
            'relationship_category_id' => $category->id,
            'direction' => 'directed',
            'role_a' => 'Godparent',
            'role_b' => 'Godchild',
            'is_active' => '1',
        ])
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('relationship_types', [
            'account_id' => $user->account_id,
            'name' => 'Godparent / Godchild',
            'role_b' => 'Godchild',
            'is_symmetric' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_type_with_nothing_written_in_it(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.relationshipTypes.create'), [])
            ->assertSessionHasErrors(['name', 'relationship_category_id', 'direction', 'role_a']);
    }

    #[Test]
    public function it_refuses_a_directed_type_with_only_one_role(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.relationshipTypes.create'), [
            'name' => 'Mentor / Mentee',
            'relationship_category_id' => $category->id,
            'direction' => 'directed',
            'role_a' => 'Mentor',
        ])->assertSessionHasErrors(['role_b']);
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)->post(route('settings.relationshipTypes.create'), [
            'name' => 'Beet buddy',
            'relationship_category_id' => $category->id,
            'direction' => 'symmetric',
            'role_a' => 'Beet buddy',
        ])->assertSessionHasErrors(['relationship_category_id']);
    }

    #[Test]
    public function it_marks_deletion_unavailable_for_a_supplied_type(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.relationshipTypes.edit', $type))
            ->assertStatus(200)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('aria-describedby="relationship-type-delete-reason"', false);
    }

    #[Test]
    public function it_rewords_a_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->actingAs($user)->put(route('settings.relationshipTypes.update', $type), [
            'name' => 'Flatmate',
            'relationship_category_id' => $category->id,
            'direction' => 'symmetric',
            'role_a' => 'Flatmate',
            'is_active' => '1',
        ])
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $type->refresh();

        $this->assertEquals('Flatmate', $type->name);
        $this->assertEquals('Flatmate', $type->role_b);
    }

    #[Test]
    public function it_keeps_the_direction_of_a_supplied_type_whatever_the_form_sends(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->actingAs($user)->put(route('settings.relationshipTypes.update', $type), [
            'name' => 'Parent / Child',
            'relationship_category_id' => $category->id,
            'direction' => 'symmetric',
            'role_a' => 'Parent',
            'role_b' => 'Child',
            'is_active' => '1',
        ])->assertRedirect(route('settings.relationshipTypes.index'));

        $this->assertFalse($type->refresh()->is_symmetric);
    }

    #[Test]
    public function it_retires_a_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->actingAs($user)->put(route('settings.relationshipTypes.update', $type), [
            'name' => 'Neighbour',
            'relationship_category_id' => $category->id,
            'direction' => 'symmetric',
            'role_a' => 'Neighbour',
        ])->assertRedirect(route('settings.relationshipTypes.index'));

        $this->assertFalse($type->refresh()->is_active);
    }

    #[Test]
    public function it_deletes_a_type_nobody_uses(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->delete(route('settings.relationshipTypes.destroy', $type))
            ->assertRedirect(route('settings.relationshipTypes.index'))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($type);
    }
}
