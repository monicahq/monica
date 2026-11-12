<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateKnowledgeListColumn;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeListColumnTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);

        $response = MonicaServer::actingAs($user)->tool(CreateKnowledgeListColumn::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'name' => 'City']);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_list_columns', ['knowledge_list_id' => $list->id, 'name' => 'City', 'position' => 2]);
    }
}
