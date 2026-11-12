<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VersionStatusEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\PublishTemplateVersion;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_publishes_a_draft(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $draft->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        EricaServer::actingAs($user)->tool(PublishTemplateVersion::class, ['template' => $template->uuid, 'version' => 1])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals(VersionStatusEnum::Published, $draft->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_version_that_is_already_published(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(PublishTemplateVersion::class, ['template' => $template->uuid, 'version' => 1])
            ->assertHasErrors(['Version 1 is not a draft.']);
    }
}
