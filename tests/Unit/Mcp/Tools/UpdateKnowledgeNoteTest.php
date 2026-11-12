<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateKnowledgeNote;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_text_of_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies', 'body' => '## Awards']);

        $response = MonicaServer::actingAs($user)->tool(UpdateKnowledgeNote::class, ['vault' => $vault->slug, 'note' => $note->uuid, 'body' => 'Best dressed']);

        $response->assertOk();
        $this->assertEquals('Best dressed', $note->refresh()->body);
        $this->assertEquals('Dundies', $note->title);
    }
}
