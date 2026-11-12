<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VersionStatusEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateTemplateSection;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_title_when_only_the_folding_changes(): void
    {
        [$user, $template, $row] = $this->placement();

        MonicaServer::actingAs($user)->tool(UpdateTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'is_collapsed_by_default' => true,
        ])->assertOk()->assertHasNoErrors();

        $row->refresh();
        $this->assertSame('Scranton branch', $row->title_override);
        $this->assertTrue($row->is_collapsed_by_default);
    }

    #[Test]
    public function it_refuses_a_published_version(): void
    {
        [$user, $template, $row] = $this->placement();
        $row->structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        MonicaServer::actingAs($user)->tool(UpdateTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $row->id,
            'title_override' => 'Stamford branch',
        ])->assertHasErrors();

        $this->assertSame('Scranton branch', $row->refresh()->title_override);
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
            'title_override' => 'Scranton branch',
        ]);

        return [$user, $template, $row];
    }
}
