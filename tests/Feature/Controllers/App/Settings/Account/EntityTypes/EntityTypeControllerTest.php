<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_the_account(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'wine-bottles',
        ]);

        $this->actingAs($user)->get(route('settings.entityTypes.index'))
            ->assertStatus(200)
            ->assertSee('Wine bottle')
            ->assertSee('Wine bottles')
            ->assertSee('wine-bottles');
    }

    #[Test]
    public function it_says_when_a_type_is_not_active(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.entityTypes.index'))
            ->assertStatus(200)
            ->assertSee('Not active');
    }

    #[Test]
    public function it_shows_the_form_that_declares_a_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.entityTypes.new'))
            ->assertStatus(200)
            ->assertSee('Create a kind of record')
            ->assertSee('Create type');
    }

    #[Test]
    public function it_creates_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'description' => 'What is in the cellar',
            'icon' => 'device',
            'colour' => '#4f8a49',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('settings.entityTypes.index'));
        $response->assertSessionHas('status', 'The type is created.');

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'wine-bottle',
            'icon' => 'device',
            'colour' => '#4f8a49',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_a_type_that_is_not_active_yet(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertRedirect(route('settings.entityTypes.index'));

        $this->assertDatabaseHas('entity_types', [
            'name_singular' => 'Wine bottle',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_takes_the_slug_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'cellar',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertRedirect(route('settings.entityTypes.index'));

        $this->assertDatabaseHas('entity_types', [
            'name_singular' => 'Wine bottle',
            'slug' => 'cellar',
        ]);
    }

    #[Test]
    public function it_falls_back_to_the_singular_name_when_no_slug_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => '',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertRedirect(route('settings.entityTypes.index'));

        $this->assertDatabaseHas('entity_types', ['slug' => 'wine-bottle']);
    }

    #[Test]
    public function it_refuses_a_slug_that_is_not_a_slug(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        foreach (['Wine Bottles', 'wine bottles', 'cavé', 'wine_bottles', '-wine', 'wine-'] as $slug) {
            $this->actingAs($user)->post(route('settings.entityTypes.create'), [
                'name_singular' => 'Wine bottle',
                'name_plural' => 'Wine bottles',
                'slug' => $slug,
                'icon' => 'device',
                'colour' => '#4f8a49',
            ])->assertSessionHasErrors('slug');
        }

        $this->assertSame(0, EntityType::query()->count());
    }

    #[Test]
    public function it_refuses_a_slug_another_type_in_the_account_already_uses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'cellar']);

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'cellar',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertSessionHasErrors('slug');
    }

    #[Test]
    public function it_refuses_a_slug_a_deleted_type_still_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'cellar'])->delete();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'cellar',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertSessionHasErrors('slug');
    }

    #[Test]
    public function it_lets_another_account_use_the_same_slug(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        EntityType::factory()->create(['account_id' => $dwight->account_id, 'slug' => 'cellar']);

        $this->actingAs($michael)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'cellar',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $michael->account_id,
            'slug' => 'cellar',
        ]);
    }

    #[Test]
    public function it_offers_the_slug_as_a_field_while_declaring_and_never_after(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animals']);

        $this->actingAs($user)->get(route('settings.entityTypes.new'))
            ->assertStatus(200)
            ->assertSee('name="slug"', escape: false)
            ->assertSee('No spaces, no accents, no capitals');

        $this->actingAs($user)->get(route('settings.entityTypes.edit', $entityType))
            ->assertStatus(200)
            ->assertDontSee('name="slug"', escape: false)
            ->assertSee('It cannot be changed now the type exists');
    }

    #[Test]
    public function it_ignores_a_slug_sent_to_the_update_route(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animals']);

        $this->actingAs($user)->put(route('settings.entityTypes.update', $entityType), [
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'slug' => 'something-else',
            'icon' => 'profile',
            'colour' => '#3f5fd0',
            'is_active' => '1',
        ])->assertRedirect(route('settings.entityTypes.index'));

        $this->assertEquals('animals', $entityType->refresh()->slug);
    }

    #[Test]
    public function it_says_the_description_may_be_left_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.entityTypes.new'))
            ->assertStatus(200)
            ->assertSee('optional');
    }

    #[Test]
    public function it_refuses_a_type_without_both_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'icon' => 'device',
            'colour' => '#4f8a49',
        ])->assertSessionHasErrors(['name_singular', 'name_plural']);
    }

    #[Test]
    public function it_refuses_an_icon_and_a_colour_it_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.entityTypes.create'), [
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'icon' => 'skull',
            'colour' => '#ff0000',
        ])->assertSessionHasErrors(['icon', 'colour']);
    }

    #[Test]
    public function it_shows_the_form_that_changes_a_type(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'slug' => 'animals',
        ]);

        $this->actingAs($user)->get(route('settings.entityTypes.edit', $entityType))
            ->assertStatus(200)
            ->assertSee('Edit Animal')
            ->assertSee('Save changes')
            ->assertSee($entityType->uuid);
    }

    #[Test]
    public function it_updates_a_type_and_leaves_its_slug_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'slug' => 'animals',
        ]);

        $response = $this->actingAs($user)->put(route('settings.entityTypes.update', $entityType), [
            'name_singular' => 'Pet',
            'name_plural' => 'Pets',
            'icon' => 'profile',
            'colour' => '#3f5fd0',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('settings.entityTypes.index'));
        $response->assertSessionHas('status', 'The type is saved.');

        $entityType->refresh();

        $this->assertEquals('Pet', $entityType->name_singular);
        $this->assertEquals('animals', $entityType->slug);
    }

    #[Test]
    public function it_turns_a_type_off_and_on_from_the_form(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animals']);

        $fields = [
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'icon' => 'profile',
            'colour' => '#3f5fd0',
        ];

        $this->actingAs($user)->put(route('settings.entityTypes.update', $entityType), $fields);

        $this->assertFalse($entityType->refresh()->is_active);

        $this->actingAs($user)->put(route('settings.entityTypes.update', $entityType), $fields + ['is_active' => '1']);

        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_deletes_a_type_that_holds_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.entityTypes.destroy', $entityType));

        $response->assertRedirect(route('settings.entityTypes.index'));
        $response->assertSessionHas('status', 'The type is deleted.');

        $this->assertSoftDeleted($entityType);
    }

    #[Test]
    public function it_keeps_a_type_that_still_holds_records(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.entityTypes.destroy', $entityType));

        $response->assertRedirect(route('settings.entityTypes.index'));
        $response->assertSessionHas('error', 'The type still holds records and stays.');

        $this->assertNotSoftDeleted($entityType);
    }

    #[Test]
    public function it_marks_deletion_unavailable_for_a_type_that_holds_records(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->used()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.entityTypes.edit', $entityType))
            ->assertStatus(200)
            ->assertSee('Delete this type')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('aria-describedby="entity-type-delete-reason"', false)
            ->assertSee('This type cannot be deleted while records are filed under it.');
    }

    #[Test]
    public function it_finds_the_type_of_the_signed_in_account_when_two_share_a_slug(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        EntityType::factory()->create([
            'account_id' => $dwight->account_id,
            'slug' => 'animals',
            'name_singular' => 'Beet',
        ]);
        EntityType::factory()->create([
            'account_id' => $michael->account_id,
            'slug' => 'animals',
            'name_singular' => 'Animal',
        ]);

        $this->actingAs($michael)->get(route('settings.entityTypes.edit', 'animals'))
            ->assertStatus(200)
            ->assertSee('Edit Animal')
            ->assertDontSee('Edit Beet');
    }
}
