<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VersionStatusEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateTemplateDraft;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTemplateDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_draft_of_a_published_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        EricaServer::actingAs($user)->tool(CreateTemplateDraft::class, ['template' => $template->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_versions', [
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'status' => VersionStatusEnum::Draft->value,
        ]);
    }

    #[Test]
    public function it_returns_the_open_draft_rather_than_a_second_one(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        EricaServer::actingAs($user)->tool(CreateTemplateDraft::class, ['template' => $template->uuid])->assertOk();
        EricaServer::actingAs($user)->tool(CreateTemplateDraft::class, ['template' => $template->uuid])->assertOk();

        $this->assertSame(1, StructureVersion::query()
            ->where('structure_id', $template->structure->id)
            ->where('status', VersionStatusEnum::Draft)
            ->count());
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions.create', app(CreateTemplateDraft::class)->mirrors());
    }
}
