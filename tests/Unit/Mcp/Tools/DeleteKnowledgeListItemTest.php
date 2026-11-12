<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteKnowledgeListItem;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Blue Cross']);

        $response = MonicaServer::actingAs($user)->tool(DeleteKnowledgeListItem::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'item' => $item->uuid]);

        $response->assertOk();
        $this->assertModelMissing($item);
    }
}
