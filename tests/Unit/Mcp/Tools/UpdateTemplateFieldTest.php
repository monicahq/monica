<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VersionStatusEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateTemplateField;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_what_was_left_out(): void
    {
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'label' => 'Assistant to the regional manager',
            'help_text' => 'Not assistant regional manager',
            'is_sensitive' => true,
        ]);

        MonicaServer::actingAs($user)->tool(UpdateTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'field' => (string) $field->id,
            'is_required' => true,
        ])->assertOk()->assertHasNoErrors();

        $field->refresh();
        $this->assertTrue($field->is_required);
        $this->assertTrue($field->is_sensitive);
        $this->assertSame('Not assistant regional manager', $field->help_text);
        $this->assertSame('Assistant to the regional manager', $field->label);
    }

    #[Test]
    public function it_changes_a_field_of_a_published_section_in_a_draft_of_it(): void
    {
        [$user, $template, $row] = $this->placement();
        $published = $row->sectionVersion;
        $field = Field::factory()->create(['section_version_id' => $published->id, 'label' => 'Street']);
        $published->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        MonicaServer::actingAs($user)->tool(UpdateTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'field' => (string) $field->id,
            'label' => 'Road',
        ])->assertOk()->assertHasNoErrors()->assertSee('Road');

        $this->assertSame('Street', $field->refresh()->label);
        $this->assertSame(['Road'], $row->refresh()->sectionVersion->fields()->pluck('label')->all());
    }

    /** @return array{User, Template, StructureVersionSection} */
    private function placement(): array
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        return [$user, $template, $row];
    }
}
