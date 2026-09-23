<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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

        $row = StructureVersionSection::query()->where('account_id', $user->account_id)->firstOrFail();

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
        $this->assertEquals($version->id, $template->structure->published_version_id);
        $this->assertEquals(VersionStatusEnum::Published, $row->fresh()->sectionVersion->status);
    }

    #[Test]
    public function it_gives_a_shared_section_its_own_version_before_editing_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templates.create'), ['name' => 'Pet', 'applies_to' => 'contact']);
        $template = Template::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health']);
        $row = StructureVersionSection::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->actingAs($user)->post(route('settings.fields.create', [$template, $row]), [
            'type' => FieldTypeEnum::Text->value,
            'label' => 'Microchip',
        ]);

        $field = Field::query()->where('account_id', $user->account_id)->firstOrFail();
        $sectionVersion = $row->fresh()->sectionVersion;

        $other = Template::factory()->create(['account_id' => $user->account_id]);
        $otherVersion = StructureVersion::factory()->create(['structure_id' => $other->structure->id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $otherVersion->id,
            'section_version_id' => $sectionVersion->id,
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
