<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetKnowledgeList;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetKnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_list_with_its_items(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Blue Cross']);

        $response = MonicaServer::actingAs($user)->tool(GetKnowledgeList::class, ['vault' => $vault->slug, 'list' => $list->uuid]);

        $response->assertOk();
        $response->assertSee('Blue Cross');
    }

    #[Test]
    public function it_reports_a_list_of_another_vault_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $elsewhere->id])->id]);

        MonicaServer::actingAs($user)->tool(GetKnowledgeList::class, ['vault' => $vault->slug, 'list' => $list->uuid])
            ->assertHasErrors();
    }
}
