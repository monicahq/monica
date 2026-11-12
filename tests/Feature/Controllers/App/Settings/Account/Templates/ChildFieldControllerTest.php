<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChildFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_what_goes_inside_the_repeater_and_offers_no_repeater(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $caregivers = $this->repeaterIn($row);

        $this->actingAs($user)->get(route('settings.childFields.new', [$template, $row, $caregivers]))
            ->assertStatus(200)
            ->assertSee('name="parent_id" value="'.$caregivers->id.'"', escape: false)
            ->assertDontSee("previewed === 'repeater'", escape: false);
    }

    #[Test]
    public function it_adds_a_field_inside_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $caregivers = $this->repeaterIn($row);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Relation->value,
            'label' => 'Person',
            'relation_target' => 'contact',
            'parent_id' => $caregivers->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('fields', ['label' => 'Person', 'parent_field_id' => $caregivers->id]);
    }

    #[Test]
    public function it_adds_a_field_inside_the_copy_of_a_published_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $caregivers = $this->repeaterIn($row);
        $row->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Text->value,
            'label' => 'Role',
            'parent_id' => $caregivers->id,
        ])->assertRedirect();

        $copy = $row->refresh()->sectionVersion->fields()->where('key', 'caregivers')->firstOrFail();

        $this->assertNotEquals($caregivers->id, $copy->id);
        $this->assertDatabaseHas('fields', ['label' => 'Role', 'parent_field_id' => $copy->id]);
        $this->assertDatabaseMissing('fields', ['label' => 'Role', 'parent_field_id' => $caregivers->id]);
    }

    #[Test]
    public function it_refuses_a_repeater_inside_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $caregivers = $this->repeaterIn($row);

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Repeater->value,
            'label' => 'Shifts',
            'parent_id' => $caregivers->id,
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('fields', ['label' => 'Shifts']);
    }

    #[Test]
    public function it_answers_not_found_when_the_parent_is_not_a_repeater(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $weight = Field::factory()->create(['section_version_id' => $row->section_version_id, 'type' => FieldTypeEnum::Number]);

        $this->actingAs($user)->get(route('settings.childFields.new', [$template, $row, $weight]))->assertNotFound();

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Text->value,
            'label' => 'Unit',
            'parent_id' => $weight->id,
        ])->assertNotFound();
    }

    private function repeaterIn(StructureVersionSection $row): Field
    {
        return Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
            'key' => 'caregivers',
            'label' => 'Caregivers',
        ]);
    }

    /** @return array{0: Template, 1: StructureVersionSection} */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'People']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
