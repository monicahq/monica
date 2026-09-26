<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateTemplateField;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTemplateFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_field_to_a_section_of_a_draft(): void
    {
        [$user, $template, $row] = $this->placement();

        EricaServer::actingAs($user)->tool(CreateTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'type' => 'text',
            'label' => 'Beet variety',
        ])->assertOk()->assertHasNoErrors()->assertSee('Beet variety');

        $this->assertDatabaseHas('fields', ['section_version_id' => $row->section_version_id, 'label' => 'Beet variety', 'type' => 'text']);
    }

    #[Test]
    public function it_refuses_a_repeater_inside_a_repeater(): void
    {
        [$user, $template, $row] = $this->placement();
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $row->section_version_id]);

        EricaServer::actingAs($user)->tool(CreateTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'type' => 'repeater',
            'label' => 'Harvests',
            'parent_id' => $repeater->id,
        ])->assertHasErrors(['A repeater cannot sit inside another repeater.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions.sections.fields.create', app(CreateTemplateField::class)->mirrors());
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
