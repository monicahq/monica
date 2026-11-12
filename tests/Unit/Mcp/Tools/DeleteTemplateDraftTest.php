<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteTemplateDraft;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteTemplateDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_draft(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);
        $draft = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $published->id,
        ]);

        EricaServer::actingAs($user)->tool(DeleteTemplateDraft::class, ['template' => $template->uuid, 'version' => 2])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($draft);
    }

    #[Test]
    public function it_refuses_a_published_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(DeleteTemplateDraft::class, ['template' => $template->uuid, 'version' => 1])
            ->assertHasErrors(['has been published and cannot be deleted']);

        $this->assertModelExists($published);
    }
}
