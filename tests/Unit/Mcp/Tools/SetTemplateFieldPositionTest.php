<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetTemplateFieldPosition;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetTemplateFieldPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_in_its_section(): void
    {
        [$user, $template, $row] = $this->placement();
        $first = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);
        $second = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 2]);

        EricaServer::actingAs($user)->tool(SetTemplateFieldPosition::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'field' => (string) $second->id,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions.sections.fields.position.update', app(SetTemplateFieldPosition::class)->mirrors());
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
