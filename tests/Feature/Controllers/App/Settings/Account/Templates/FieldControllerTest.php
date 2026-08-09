<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
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
