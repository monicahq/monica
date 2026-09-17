<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Settings;

use App\Actions\CreateActivityType;
use App\Actions\ReplaceActivityTypeFields;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypeFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_everything_the_form_sends(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.new'));

        $response->assertSee('name="name"', false);
        $response->assertSee('name="activity_category_id"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="icon"', false);
        $response->assertSee('name="colour"', false);
        $response->assertSee('name="is_active"', false);
        $response->assertSee('action="'.route('settings.activityTypes.create').'"', false);
    }

    #[Test]
    public function it_offers_the_type_or_retires_it_from_the_header(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.new'));

        $response->assertSee('role="switch"', false);
        $response->assertSee('name="is_active"', false);
        $response->assertSee('form="activity-type-form"', false);
        $response->assertSee('aria-describedby="activity-type-availability"', false);
        $response->assertSee('id="activity-type-availability"', false);
        $response->assertSee('Offered when recording');
        $response->assertSee('Switch this off to retire the type.', false);
    }

    #[Test]
    public function it_picks_the_icon_and_the_colour_from_two_pickers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.new'));

        $response->assertSee('id="activity-type-mark-icon-picker"', false);
        $response->assertSee('id="activity-type-mark-colour-picker"', false);
        $response->assertSee('name="icon"', false);
        $response->assertSee('name="colour"', false);
        $response->assertSee('Pick an icon');
        $response->assertSee('Pick a colour');
    }

    #[Test]
    public function it_edits_the_fields_on_the_same_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.new'));

        $response->assertSee('x-data="activityTypeFields"', false);
        $response->assertSee('data-field-editor', false);
        $response->assertSee('@click="addBlock($el)"', false);
        $response->assertSee('data-block-template', false);
        $response->assertSee('data-field-template', false);
        $response->assertSee('data-choice-template', false);
    }

    #[Test]
    public function it_shows_the_block_for_the_fields_in_no_group_even_when_there_are_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.new'));

        $response->assertSee('data-loose', false);
        $response->assertSee('Not in a group');
    }

    #[Test]
    public function it_reads_a_saved_field_back_into_its_row(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateActivityType(
            user: $user,
            activityCategory: $category,
            name: 'Medical appointment',
        )->execute();

        new ReplaceActivityTypeFields(
            user: $user,
            activityType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [[
                'key' => null,
                'group' => 'g1',
                'label' => 'Who you saw',
                'type' => 'text',
                'is_required' => true,
                'help_text' => 'The doctor, the clinic, whoever it was',
                'settings' => ['placeholder' => 'Dr Meyer'],
                'choices' => [],
            ]],
        )->execute();

        $response = $this->actingAs($user)->get(route('settings.activityTypes.edit', $type));

        $response->assertSee('value="Who you saw"', false);
        $response->assertSee('value="The appointment"', false);
        $response->assertSee('value="Dr Meyer"', false);
        $response->assertSee('The doctor, the clinic, whoever it was', false);
    }

    #[Test]
    public function it_reads_the_choices_of_a_field_back_into_its_row(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateActivityType(
            user: $user,
            activityCategory: $category,
            name: 'Medical appointment',
        )->execute();

        new ReplaceActivityTypeFields(
            user: $user,
            activityType: $type,
            groups: [],
            fields: [[
                'key' => null,
                'group' => '',
                'label' => 'Kind of appointment',
                'type' => 'select',
                'is_required' => false,
                'help_text' => null,
                'settings' => [],
                'choices' => ['Check-up', 'Follow-up'],
            ]],
        )->execute();

        $response = $this->actingAs($user)->get(route('settings.activityTypes.edit', $type));

        $response->assertSee('value="Check-up"', false);
        $response->assertSee('value="Follow-up"', false);
        $response->assertSee('[choices][]"', false);
    }

    #[Test]
    public function it_points_a_refused_delete_at_its_reason(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.edit', $type));

        $response->assertSee('aria-describedby="activity-type-delete-reason"', false);
        $response->assertSee('id="activity-type-delete-reason"', false);
        $response->assertSee('The type came with your account and stays.');
    }

    #[Test]
    public function it_offers_a_real_delete_for_a_type_the_user_made(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.activityTypes.edit', $type));

        $response->assertSee(route('settings.activityTypes.destroy', $type), false);
        $response->assertSee('This cannot be undone.', false);
        $response->assertDontSee('aria-describedby="activity-type-delete-reason"', false);
    }

    #[Test]
    public function it_never_shows_the_words_the_screen_exists_to_keep_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        $html = $this->actingAs($user)->get(route('settings.activityTypes.edit', $type))->getContent();

        $start = strpos($html, '<h1');
        $screen = strip_tags(substr($html, (int) $start, (int) strpos($html, '</main>', (int) $start) - (int) $start));

        foreach (['template', 'structure', 'version'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $screen);
        }
    }
}
