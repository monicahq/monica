<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\AddTemplateSection;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
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

        MonicaServer::actingAs($user)->tool(AddTemplateSection::class, [
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

        MonicaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'name' => 'Beet farm',
        ])->assertHasErrors();

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

        MonicaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section_id' => $section->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame($sectionVersion->id, $version->assembly()->sole()->section_version_id);
    }
}
