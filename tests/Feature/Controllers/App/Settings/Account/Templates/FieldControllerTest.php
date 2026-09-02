<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_catalogue_of_types(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertStatus(200)
            ->assertSee(__('Short text'))
            ->assertSee(__('Recurring date'))
            ->assertSee(__('Later'));
    }

    #[Test]
    public function it_draws_the_field_every_type_produces(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $response = $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]));

        $response->assertStatus(200)
            ->assertSee(__('Nothing picked yet'))
            ->assertSee(__('On the record'))
            ->assertSee(__('For example: :example.', ['example' => __('Adoption day')]));

        foreach ((new FieldCatalogueViewModel)->types() as $type) {
            $response->assertSee("previewed === '{$type['key']}'", escape: false);
        }
    }

    #[Test]
    public function it_says_in_words_what_the_panel_is_previewing(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertSee('aria-live="polite"', escape: false)
            ->assertSee('Short text. One line of text, up to a length you set.');
    }

    #[Test]
    public function it_offers_a_filter_over_the_catalogue(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $catalogue = new FieldCatalogueViewModel;

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertSee(__('Filter the types'))
            ->assertSee($catalogue->totalPhrase())
            ->assertSee(__('Nothing matches that.'))
            ->assertSee(__('Clear the filter'))
            ->assertSee('recurring date recurring_date');
    }

    #[Test]
    public function it_names_the_section_and_the_chosen_type_in_the_footer(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $section = $row->sectionVersion->section->name;

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertSee(__('Pick a type to carry on.'))
            ->assertSee((new FieldCatalogueViewModel)->footerLines($section)['text']);
    }

    #[Test]
    public function it_puts_the_bar_outside_the_form_and_the_cards_on_a_layer_of_their_own(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertSeeInOrder(['</form>', __('Pick a type to carry on.')], escape: false)
            ->assertSee('form="add-field"', escape: false)
            ->assertSee('relative flex cursor-pointer', escape: false);
    }

    #[Test]
    public function it_asks_where_the_choices_come_from(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Species']);

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))
            ->assertSee(__('Where do the choices come from?'))
            ->assertSee($list->name)
            ->assertSee('needsAList', escape: false);
    }

    #[Test]
    public function it_hands_the_label_and_the_type_back_after_a_refusal(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $response = $this->actingAs($user)
            ->from(route('settings.fields.new', [$template, $row]))
            ->followingRedirects()
            ->post(route('settings.fields.create', [$template, $row]), [
                'type' => FieldTypeEnum::Number->value,
                'label' => str_repeat('Microchip number', 20),
            ]);

        $response->assertStatus(200)
            ->assertSee('Microchip number')
            ->assertSeeInOrder(['value="number"', 'checked'], escape: false);
    }

    #[Test]
    public function it_adds_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Number->value,
            'label' => 'Weight',
        ])->assertRedirect();

        $this->assertDatabaseHas('fields', [
            'section_version_id' => $row->section_version_id,
            'type' => FieldTypeEnum::Number->value,
            'label' => 'Weight',
            'key' => 'weight',
        ]);
    }

    #[Test]
    public function it_adds_a_choice_field_with_the_list_it_reads_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Select->value,
            'label' => 'Species',
            'value_list_id' => $list->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('fields', ['label' => 'Species', 'value_list_id' => $list->id]);
    }

    #[Test]
    public function it_explains_why_a_choice_field_needs_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Select->value,
            'label' => 'Species',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('fields', ['label' => 'Species']);
    }

    #[Test]
    public function it_refuses_a_type_the_first_version_does_not_ship(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => 'money',
            'label' => 'Insurance',
        ])->assertSessionHasErrors('type');
    }

    #[Test]
    public function it_refuses_a_value_list_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($michael);
        $theirs = ValueList::factory()->create();

        $this->actingAs($michael)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Select->value,
            'label' => 'Species',
            'value_list_id' => $theirs->id,
        ])->assertSessionHasErrors('value_list_id');
    }

    #[Test]
    public function it_saves_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'key' => 'weight']);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Weight in kilograms',
            'help_text' => 'To one decimal',
            'is_sensitive' => '1',
        ])->assertRedirect();

        $field->refresh();

        $this->assertEquals('Weight in kilograms', $field->label);
        $this->assertEquals('To one decimal', $field->help_text);
        $this->assertTrue($field->is_sensitive);
    }

    /**
     * Which end a repeater collects at is settled here, beside its label. It
     * says where a new repetition lands and nothing about where an existing one
     * may be moved to.
     */
    #[Test]
    public function it_saves_which_end_a_repeater_collects_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'key' => 'notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Notes',
            'repetition_order' => RepetitionOrderEnum::NewestFirst->value,
        ])->assertRedirect();

        $this->assertSame(RepetitionOrderEnum::NewestFirst, $field->refresh()->repetition_order);
    }

    #[Test]
    public function it_refuses_an_end_it_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'key' => 'notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Notes',
            'repetition_order' => 'sideways',
        ])->assertSessionHasErrors('repetition_order');
    }

    /**
     * A field that is not a repeater has no direction to keep, so storing one
     * would be storing a setting its type cannot honour.
     */
    #[Test]
    public function it_keeps_no_end_on_a_field_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'key' => 'weight',
            'type' => FieldTypeEnum::Number,
        ]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Weight',
            'repetition_order' => RepetitionOrderEnum::NewestFirst->value,
        ])->assertRedirect();

        $this->assertNull($field->refresh()->repetition_order);
    }

    /**
     * The key is what an export or an API caller holds on to, so it survives a
     * rename untouched.
     */
    #[Test]
    public function it_leaves_the_key_alone_when_the_label_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'key' => 'weight']);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), ['label' => 'Mass']);

        $this->assertEquals('weight', $field->fresh()->key);
    }

    #[Test]
    public function it_refuses_a_field_with_no_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [])
            ->assertSessionHasErrors('label');
    }

    #[Test]
    public function it_refuses_a_field_that_is_not_in_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $elsewhere = Field::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $elsewhere]), ['label' => 'Nope'])
            ->assertNotFound();
    }

    #[Test]
    public function it_removes_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id]);

        $this->actingAs($user)->delete(route('settings.fields.destroy', [$template, $row, $field]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertModelMissing($field);
    }

    /**
     * Editing a section a published template carries must not reach the
     * published version: it gets a copy, and the frozen one stays frozen.
     */
    #[Test]
    public function it_copies_a_published_section_rather_than_writing_to_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'key' => 'weight', 'label' => 'Weight']);
        $row->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), ['label' => 'Mass'])
            ->assertRedirect();

        $row->refresh();

        $this->assertNotEquals($field->section_version_id, $row->section_version_id);
        $this->assertEquals('Weight', $field->fresh()->label);
        $this->assertEquals('Mass', $row->sectionVersion->fields()->where('key', 'weight')->firstOrFail()->label);
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $row = TemplateVersionSection::factory()->create();

        $this->actingAs($michael)->get(route('settings.fields.new', [$template, $row]))->assertNotFound();
    }

    /**
     * The row is addressed by its own number under the template it sits on, and
     * neither half can be trusted on its own: a row of the user's own but on a
     * different template must not be reachable through this URL.
     */
    #[Test]
    public function it_refuses_an_assembly_that_is_not_on_this_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        [, $elsewhere] = $this->templateCarryingASection($user);

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $elsewhere]))->assertNotFound();

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $elsewhere]), [
            'type' => FieldTypeEnum::Text->value,
            'label' => 'Sneaky',
        ])->assertNotFound();

        $this->assertDatabaseMissing('fields', ['label' => 'Sneaky']);
    }

    /**
     * @return array{0: Template, 1: TemplateVersionSection}
     */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Health']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
