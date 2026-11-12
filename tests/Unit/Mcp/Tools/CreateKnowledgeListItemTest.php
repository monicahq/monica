<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateKnowledgeListItem;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);

        $response = MonicaServer::actingAs($user)->tool(CreateKnowledgeListItem::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'cells' => [$name->uuid => 'Blue Cross']]);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_column_id' => $name->id, 'value' => 'Blue Cross']);
    }

    #[Test]
    public function it_tells_the_assistant_a_cell_names_no_column_of_the_list(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name']);

        MonicaServer::actingAs($user)->tool(CreateKnowledgeListItem::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'cells' => ['Name' => 'Blue Cross']])
            ->assertHasErrors(['No column of this list has this identifier.']);
    }
}
