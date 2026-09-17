<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\ActivityTypes;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_type_of_the_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Leisure and culture']);
        ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'name' => 'Concert',
        ]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.index'));

        $response->assertStatus(200);
        $response->assertSee('Leisure and culture');
        $response->assertSee('Concert');
        $response->assertSee('Every type');
    }

    #[Test]
    public function it_lists_the_types_of_one_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $wanted = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Health and wellbeing']);
        $other = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Work and education']);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $wanted->id, 'name' => 'Therapy']);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $other->id, 'name' => 'Interview']);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.category', $wanted));

        $response->assertStatus(200);
        $response->assertSee('Therapy');
        $response->assertDontSee('Interview');
    }

    #[Test]
    public function it_shows_a_type_that_is_switched_off_rather_than_hiding_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'name' => 'Household task',
        ]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.category', $category));

        $response->assertStatus(200);
        $response->assertSee('Household task');
        $response->assertSee('Not offered');
    }

    #[Test]
    public function it_shows_nothing_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        ActivityType::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Beet farming']);

        $response = $this->actingAs($michael)->get(route('settings.activityTypes.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Beet farming');
    }

    #[Test]
    public function it_answers_not_found_for_a_category_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)
            ->get(route('settings.activityTypes.category', $category))
            ->assertStatus(404);
    }

    #[Test]
    public function it_opens_the_form_that_adds_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('settings.activityTypes.new'))
            ->assertStatus(200)
            ->assertSee('Add an activity type');
    }

    #[Test]
    public function it_adds_a_type_and_returns_to_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('settings.activityTypes.create'), [
            'name' => 'Coffee',
            'activity_category_id' => $category->id,
            'description' => 'A short coffee, in person',
            'icon' => 'coin',
            'colour' => '#3f5fd0',
        ]);

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('activity_types', [
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'name' => 'Coffee',
            'icon' => 'coin',
            'colour' => '#3f5fd0',
        ]);
    }

    #[Test]
    public function it_adds_a_type_with_a_group_and_a_field_in_no_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.activityTypes.create'), [
            'name' => 'Medical appointment',
            'activity_category_id' => $category->id,
            'is_active' => '1',
            'groups' => [
                'g1' => ['placement_id' => null, 'name' => 'The appointment', 'position' => 1],
            ],
            'fields' => [
                'f1' => ['key' => null, 'group' => 'g1', 'position' => 1, 'label' => 'Who you saw', 'type' => 'text', 'is_required' => '1', 'settings' => ['placeholder' => 'Dr Meyer']],
                'f2' => ['key' => null, 'group' => '', 'position' => 1, 'label' => 'Anything you were given', 'type' => 'file'],
            ],
        ])->assertRedirect(route('settings.activityTypes.category', $category));

        $type = ActivityType::query()->where('name', 'Medical appointment')->firstOrFail();
        $placements = StructureVersionSection::query()
            ->where('structure_version_id', $type->structure->published_version_id)
            ->with('sectionVersion.fields')
            ->orderBy('position')
            ->get();

        $this->assertCount(2, $placements);
        $this->assertFalse($placements->first()->shows_title);
        $this->assertSame('Anything you were given', $placements->first()->sectionVersion->fields->first()->label);
        $this->assertSame('The appointment', $placements->last()->title_override);
        $this->assertSame('Who you saw', $placements->last()->sectionVersion->fields->first()->label);
        $this->assertTrue($placements->last()->sectionVersion->fields->first()->is_required);
        $this->assertSame('Dr Meyer', $placements->last()->sectionVersion->fields->first()->placeholder);
    }

    #[Test]
    public function it_reads_the_order_the_fields_were_put_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.activityTypes.create'), [
            'name' => 'Medical appointment',
            'activity_category_id' => $category->id,
            'is_active' => '1',
            'fields' => [
                'f1' => ['group' => '', 'position' => 2, 'label' => 'What was said', 'type' => 'textarea'],
                'f2' => ['group' => '', 'position' => 1, 'label' => 'Who you saw', 'type' => 'text'],
            ],
        ])->assertRedirect(route('settings.activityTypes.category', $category));

        $type = ActivityType::query()->where('name', 'Medical appointment')->firstOrFail();
        $fields = StructureVersionSection::query()
            ->where('structure_version_id', $type->structure->published_version_id)
            ->with('sectionVersion.fields')
            ->first()
            ->sectionVersion->fields->sortBy('position')->pluck('label')->all();

        $this->assertSame(['Who you saw', 'What was said'], $fields);
    }

    #[Test]
    public function it_files_a_field_under_no_group_when_its_group_has_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.activityTypes.create'), [
            'name' => 'Medical appointment',
            'activity_category_id' => $category->id,
            'is_active' => '1',
            'fields' => [
                'f1' => ['group' => 'g9', 'position' => 1, 'label' => 'Who you saw', 'type' => 'text'],
            ],
        ]);

        $type = ActivityType::query()->where('name', 'Medical appointment')->firstOrFail();
        $placements = StructureVersionSection::query()
            ->where('structure_version_id', $type->structure->published_version_id)
            ->get();

        $this->assertCount(1, $placements);
        $this->assertFalse($placements->first()->shows_title);
    }

    #[Test]
    public function it_refuses_a_field_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->post(route('settings.activityTypes.create'), [
                'name' => 'Medical appointment',
                'activity_category_id' => $category->id,
                'is_active' => '1',
                'fields' => ['f1' => ['group' => '', 'position' => 1, 'label' => '', 'type' => 'text']],
            ])
            ->assertSessionHasErrors('fields.f1.label');

        $this->assertDatabaseCount('activity_types', 0);
    }

    #[Test]
    public function it_refuses_a_kind_of_field_that_does_not_exist(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->post(route('settings.activityTypes.create'), [
                'name' => 'Medical appointment',
                'activity_category_id' => $category->id,
                'is_active' => '1',
                'fields' => ['f1' => ['group' => '', 'position' => 1, 'label' => 'Who you saw', 'type' => 'rating']],
            ])
            ->assertSessionHasErrors('fields.f1.type');
    }

    #[Test]
    public function it_refuses_a_group_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->post(route('settings.activityTypes.create'), [
                'name' => 'Medical appointment',
                'activity_category_id' => $category->id,
                'is_active' => '1',
                'groups' => ['g1' => ['placement_id' => null, 'name' => '', 'position' => 1]],
            ])
            ->assertSessionHasErrors('groups.g1.name');
    }

    #[Test]
    public function it_stops_offering_a_type_in_the_same_save(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);

        $this->actingAs($user)->put(route('settings.activityTypes.update', $type), [
            'name' => 'Rehearsal',
            'activity_category_id' => $category->id,
            'is_active' => '0',
        ])->assertRedirect(route('settings.activityTypes.category', $category));

        $this->assertFalse($type->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_retired_type_again_in_the_same_save(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);

        $this->actingAs($user)->put(route('settings.activityTypes.update', $type), [
            'name' => 'Rehearsal',
            'activity_category_id' => $category->id,
            'is_active' => '1',
        ]);

        $this->assertTrue($type->refresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_type_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->post(route('settings.activityTypes.create'), ['name' => '', 'activity_category_id' => $category->id])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('activity_types', 0);
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)
            ->post(route('settings.activityTypes.create'), ['name' => 'Anything', 'activity_category_id' => $category->id])
            ->assertSessionHasErrors('activity_category_id');
    }

    #[Test]
    public function it_moves_a_type_to_another_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $from = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $to = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $from->id]);

        $response = $this->actingAs($user)->put(route('settings.activityTypes.update', $type), [
            'name' => 'Rehearsal',
            'activity_category_id' => $to->id,
        ]);

        $response->assertRedirect(route('settings.activityTypes.category', $to));
        $this->assertSame($to->id, $type->refresh()->activity_category_id);
    }

    #[Test]
    public function it_answers_not_found_for_a_type_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $dwight->account_id]);

        $this->actingAs($michael)
            ->get(route('settings.activityTypes.edit', $type))
            ->assertStatus(404);
    }

    #[Test]
    public function it_deletes_a_type_the_user_made(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        $response = $this->actingAs($user)->delete(route('settings.activityTypes.destroy', $type));

        $response->assertRedirect(route('settings.activityTypes.category', $category));
        $this->assertSoftDeleted($type);
    }

    #[Test]
    public function it_refuses_to_delete_a_type_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->delete(route('settings.activityTypes.destroy', $type));

        $response->assertRedirect(route('settings.activityTypes.edit', $type));
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($type);
    }
}
