<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveTemplateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_section_off_a_draft(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        EricaServer::actingAs($user)->tool(RemoveTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
        ])->assertOk()->assertHasNoErrors();

        $this->assertModelMissing($row);
    }
}
