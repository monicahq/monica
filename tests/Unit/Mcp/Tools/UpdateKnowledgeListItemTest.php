<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateKnowledgeListItem;
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

class UpdateKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_an_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Blue Cross']);

        $response = MonicaServer::actingAs($user)->tool(UpdateKnowledgeListItem::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'item' => $item->uuid, 'cells' => [$name->uuid => 'Harper Collins']]);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'value' => 'Harper Collins']);
    }

    #[Test]
    public function it_reports_an_item_of_another_list_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $clients = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id]);
        $suppliers = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id]);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $clients->id]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $suppliers->id]);

        MonicaServer::actingAs($user)->tool(UpdateKnowledgeListItem::class, ['vault' => $vault->slug, 'list' => $clients->uuid, 'item' => $item->uuid, 'cells' => [$name->uuid => 'Harper Collins']])
            ->assertHasErrors();
    }
}
