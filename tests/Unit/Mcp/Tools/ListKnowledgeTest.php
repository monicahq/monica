<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListKnowledge;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_folders_with_their_notes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies', 'body' => '## Awards']);

        $response = MonicaServer::actingAs($user)->tool(ListKnowledge::class, ['vault' => $vault->slug]);

        $response->assertOk();
        $response->assertSee('Office')->assertSee('Dundies');
    }
}
