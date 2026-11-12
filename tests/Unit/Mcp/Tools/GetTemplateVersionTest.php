<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VersionStatusEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetTemplateVersion;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_sections_and_fields_of_a_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'status' => VersionStatusEnum::Draft]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper sales']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Quarterly target']);
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'section_version_id' => $sectionVersion->id]);

        MonicaServer::actingAs($user)->tool(GetTemplateVersion::class, ['template' => $template->uuid, 'version' => 1])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Paper sales')
            ->assertSee('Quarterly target');
    }

    #[Test]
    public function it_reports_a_version_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(GetTemplateVersion::class, ['template' => $template->uuid, 'version' => 7])
            ->assertHasErrors();
    }
}
