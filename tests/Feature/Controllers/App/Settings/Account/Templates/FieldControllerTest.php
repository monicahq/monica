<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\VersionStatusEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
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
    public function it_adds_a_relation_pointing_at_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $animals = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Relation->value,
            'label' => 'Mother',
            'relation_target' => "entity:{$animals->id}",
        ])->assertRedirect();

        $this->assertDatabaseHas('fields', [
            'label' => 'Mother',
            'relation_target' => RelationTargetEnum::Entity->value,
            'relation_entity_type_id' => $animals->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_relation_pointing_at_a_kind_of_record_that_is_turned_off(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'is_active' => false]);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Relation->value,
            'label' => 'Mother',
            'relation_target' => "entity:{$animals->id}",
        ])->assertSessionHasErrors('relation_target');

        $this->assertDatabaseMissing('fields', ['label' => 'Mother']);
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

    #[Test]
    public function it_keeps_the_settings_the_inspector_does_not_show(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'key' => 'person',
            'type' => FieldTypeEnum::Text,
            'is_unique' => true,
            'default_value' => ['Dwight Schrute'],
            'options' => ['distinct_in_list' => true],
        ]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), ['label' => 'Caregiver'])
            ->assertRedirect();

        $field->refresh();

        $this->assertEquals('Caregiver', $field->label);
        $this->assertTrue($field->is_unique);
        $this->assertEquals(['Dwight Schrute'], $field->default_value);
        $this->assertEquals(['distinct_in_list' => true], $field->options);
    }

    #[Test]
    public function it_saves_the_kinds_of_file_a_field_takes_and_keeps_its_other_settings(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'key' => 'photo',
            'type' => FieldTypeEnum::Image,
            'options' => ['accepted_mime_types' => ['image/gif'], 'shown_large' => true],
        ]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Photo',
            'accepted_mime_types' => 'image/png, image/jpeg,',
        ])->assertRedirect();

        $this->assertEquals(['shown_large' => true, 'accepted_mime_types' => ['image/png', 'image/jpeg']], $field->refresh()->options);
    }

    #[Test]
    public function it_refuses_a_kind_of_file_that_is_not_a_mime_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'key' => 'contract', 'type' => FieldTypeEnum::File]);

        $this->actingAs($user)->put(route('settings.fields.update', [$template, $row, $field]), [
            'label' => 'Contract',
            'accepted_mime_types' => 'pdf',
        ])->assertSessionHasErrors('accepted_mime_types');

        $this->assertNull($field->refresh()->options);
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

    /** @return array{0: Template, 1: StructureVersionSection} */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Health']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
