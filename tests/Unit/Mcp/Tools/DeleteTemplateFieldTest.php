<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteTemplateField;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteTemplateFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_field_from_a_draft(): void
    {
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id]);

        MonicaServer::actingAs($user)->tool(DeleteTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'field' => (string) $field->id,
        ])->assertOk()->assertHasNoErrors();

        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_reports_a_field_that_does_not_exist(): void
    {
        [$user, $template, $row] = $this->placement();

        MonicaServer::actingAs($user)->tool(DeleteTemplateField::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'field' => '999999',
        ])->assertHasErrors();
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
