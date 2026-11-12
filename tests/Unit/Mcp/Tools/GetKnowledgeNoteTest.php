<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetKnowledgeNote;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_note_with_its_text(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies', 'body' => '## Awards']);

        $response = MonicaServer::actingAs($user)->tool(GetKnowledgeNote::class, ['vault' => $vault->slug, 'note' => $note->uuid]);

        $response->assertOk();
        $response->assertSee('## Awards');
    }

    #[Test]
    public function it_reports_a_note_of_another_vault_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $elsewhere->id])->id]);

        MonicaServer::actingAs($user)->tool(GetKnowledgeNote::class, ['vault' => $vault->slug, 'note' => $note->uuid])
            ->assertHasErrors();
    }
}
