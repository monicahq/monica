<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteKnowledgeFolder;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_folder_moving_its_notes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies', 'body' => '## Awards']);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $response = MonicaServer::actingAs($user)->tool(DeleteKnowledgeFolder::class, ['vault' => $vault->slug, 'folder' => $folder->uuid, 'move_to' => $travel->uuid]);

        $response->assertOk();
        $this->assertModelMissing($folder);
        $this->assertEquals($travel->id, $note->refresh()->knowledge_folder_id);
    }

    #[Test]
    public function it_deletes_a_folder_with_its_notes_when_nothing_is_named_to_receive_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(DeleteKnowledgeFolder::class, ['vault' => $vault->slug, 'folder' => $folder->uuid])
            ->assertOk();

        $this->assertModelMissing($folder);
        $this->assertModelMissing($note);
    }

    #[Test]
    public function it_tells_the_assistant_a_folder_cannot_receive_its_own_notes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(DeleteKnowledgeFolder::class, ['vault' => $vault->slug, 'folder' => $folder->uuid, 'move_to' => $folder->uuid])
            ->assertHasErrors(['Choose another folder to move the notes and lists to.']);

        $this->assertModelExists($folder);
    }
}
