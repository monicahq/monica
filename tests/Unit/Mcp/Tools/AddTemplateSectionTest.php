<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\AddTemplateSection;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddTemplateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_new_section_in_a_draft(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'name' => 'Beet farm',
            'icon' => 'home',
        ])->assertOk()->assertHasNoErrors()->assertSee('Beet farm');

        $this->assertDatabaseHas('sections', ['account_id' => $user->account_id, 'name' => 'Beet farm', 'icon' => 'home']);
        $this->assertSame('Beet farm', $version->assembly()->sole()->sectionVersion->section->name);
    }

    #[Test]
    public function it_refuses_a_new_section_on_a_published_version_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'name' => 'Beet farm',
        ])->assertHasErrors(['This version has been published. Open a draft to change it.']);

        $this->assertDatabaseMissing('sections', ['name' => 'Beet farm']);
    }

    #[Test]
    public function it_places_a_section_the_account_already_has(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundies']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->update(['published_version_id' => $sectionVersion->id]);

        EricaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section_id' => $section->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame($sectionVersion->id, $version->assembly()->sole()->section_version_id);
    }

    #[Test]
    public function it_refuses_a_section_already_in_the_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->update(['published_version_id' => $sectionVersion->id]);
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'section_version_id' => $sectionVersion->id]);

        EricaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section_id' => $section->uuid,
        ])->assertHasErrors(['This section is already on this structure version.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions.sections.create', app(AddTemplateSection::class)->mirrors());
    }
}
