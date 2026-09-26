<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetTemplateSectionPosition;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetTemplateSectionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_on_its_page(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        [$first, $second] = collect([1, 2])->map(fn (int $position): StructureVersionSection => StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
            'position' => $position,
        ]))->all();

        EricaServer::actingAs($user)->tool(SetTemplateSectionPosition::class, [
            'template' => $template->uuid,
            'version' => 1,
            'section' => (string) $second->id,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions.sections.position.update', app(SetTemplateSectionPosition::class)->mirrors());
    }
}
