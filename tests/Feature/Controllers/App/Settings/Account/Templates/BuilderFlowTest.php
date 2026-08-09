<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The builder end to end: create a template, put a section on it, add a field,
 * edit it, publish, and edit again on a fresh draft.
 *
 * This is the one test that walks the whole path rather than a single screen,
 * because most of what can go wrong in the builder is a step handing the wrong
 * thing to the next one.
 */
class BuilderFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_walks_a_template_from_nothing_to_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.templates.create'), [
                'name' => 'Pet',
                'applies_to' => 'contact',
                'description' => 'Everything the vet asks',
            ])
            ->assertRedirect();

        $template = Template::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))->assertOk();

        $this->actingAs($user)
            ->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect();

        $row = TemplateVersionSection::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->get(route('settings.fields.new', [$template, $row]))->assertOk();

        $this->actingAs($user)
            ->post(route('settings.fields.create', [$template, $row]), [
                'type' => FieldTypeEnum::Number->value,
                'label' => 'Weight',
            ])
            ->assertRedirect();

        $field = Field::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->assertEquals('weight', $field->key);
        $this->assertEquals(FieldTypeEnum::Number, $field->type);

        $this->actingAs($user)
            ->put(route('settings.fields.update', [$template, $row, $field]), [
                'label' => 'Weight in kilograms',
                'help_text' => 'To one decimal',
                'is_required' => '1',
            ])
            ->assertRedirect();

        $field->refresh();

        $this->assertEquals('Weight in kilograms', $field->label);
        $this->assertEquals('To one decimal', $field->help_text);
        $this->assertTrue($field->is_required);
        $this->assertEquals('weight', $field->key);

        $version = $template->versions()->first();

        $this->actingAs($user)
            ->put(route('settings.templateVersionPublication.update', [$template, $version]))
            ->assertRedirect();

        $template->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $version->fresh()->status);
        $this->assertEquals($version->id, $template->published_version_id);
        $this->assertEquals(VersionStatusEnum::Published, $row->fresh()->sectionVersion->status);
    }

    #[Test]
    public function it_opens_a_published_template_read_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = $template->versions()->create([
            'account_id' => $user->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
            'record_count' => 0,
        ]);
        $template->update(['published_version_id' => $version->id]);

        $this->actingAs($user)
            ->get(route('settings.templateBuilder.index', $template))
            ->assertOk()
            ->assertSee(__('Start a draft'))
            ->assertDontSee(__('Add a section'));
    }

    /**
     * The whole point of the copy on write: a section somebody else is carrying
     * gets a new version rather than being edited underneath them.
     */
    #[Test]
    public function it_gives_a_shared_section_its_own_version_before_editing_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templates.create'), ['name' => 'Pet', 'applies_to' => 'contact']);
        $template = Template::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health']);
        $row = TemplateVersionSection::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Text->value,
            'label' => 'Microchip',
        ]);

        $field = Field::query()->where('account_id', $user->account_id)->firstOrFail();
        $sectionVersion = $row->fresh()->sectionVersion;

        // Somebody else's template starts carrying the very same section version.
        $other = Template::factory()->create(['account_id' => $user->account_id]);
        $otherVersion = $other->versions()->create([
            'account_id' => $user->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'record_count' => 0,
        ]);
        TemplateVersionSection::query()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $otherVersion->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
        ]);
        $sectionVersion->increment('usage_count');

        $this->actingAs($user)
            ->put(route('settings.fields.update', [$template, $row, $field]), ['label' => 'Chip number'])
            ->assertRedirect();

        $row->refresh();

        $this->assertNotEquals($sectionVersion->id, $row->section_version_id);
        $this->assertEquals('Chip number', $row->sectionVersion->fields()->where('key', 'microchip')->firstOrFail()->label);
        $this->assertEquals('Microchip', $field->fresh()->label);
    }
}
