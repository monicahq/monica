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
    public function it_leaves_out_the_types_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        EntityType::factory()->create([
            'account_id' => $dwight->account_id,
            'name_plural' => 'Beet varieties',
        ]);

        $this->actingAs($michael)->get(route('settings.entityTypes.index'))
            ->assertStatus(200)
            ->assertDontSee('Beet varieties');
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
            ->assertSee('Create an entity type')
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

    /**
     * The checkbox on the form and the pair of actions behind it have to agree,
     * so saving the form is what turns a type off and on.
     */
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
    public function it_offers_no_deletion_for_a_type_that_holds_records(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->used()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.entityTypes.edit', $entityType))
            ->assertStatus(200)
            ->assertDontSee('Delete this type')
            ->assertSee('This type cannot be deleted while records are filed under it.');
    }

    /**
     * The slug is only unique within an account, so two accounts may both hold
     * "animals" and each must be shown its own.
     */
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

    #[Test]
    public function it_refuses_to_touch_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $dwight->account_id,
            'slug' => 'beets',
        ]);

        $this->actingAs($michael)->get(route('settings.entityTypes.edit', 'beets'))
            ->assertNotFound();

        $this->actingAs($michael)->put(route('settings.entityTypes.update', 'beets'), [
            'name_singular' => 'Mine',
            'name_plural' => 'Mine',
            'icon' => 'profile',
            'colour' => '#3f5fd0',
        ])->assertNotFound();

        $this->actingAs($michael)->delete(route('settings.entityTypes.destroy', 'beets'))
            ->assertNotFound();

        $this->assertNotSoftDeleted($entityType);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $this->get(route('settings.entityTypes.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->get(route('settings.entityTypes.new'))
            ->assertRedirect(route('auth.login.new'));

        $this->post(route('settings.entityTypes.create'), [])
            ->assertRedirect(route('auth.login.new'));
    }
}
