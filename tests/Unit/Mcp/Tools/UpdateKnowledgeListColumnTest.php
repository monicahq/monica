<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateKnowledgeListColumn;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeListColumnTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Clients']);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);

        $response = MonicaServer::actingAs($user)->tool(UpdateKnowledgeListColumn::class, ['vault' => $vault->slug, 'list' => $list->uuid, 'column' => $name->uuid, 'name' => 'Client']);

        $response->assertOk();
        $this->assertEquals('Client', $name->refresh()->name);
    }

    #[Test]
    public function it_reports_a_column_of_another_list_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $clients = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id]);
        $suppliers = KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id]);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $suppliers->id, 'name' => 'Name']);

        MonicaServer::actingAs($user)->tool(UpdateKnowledgeListColumn::class, ['vault' => $vault->slug, 'list' => $clients->uuid, 'column' => $column->uuid, 'name' => 'Client'])
            ->assertHasErrors();

        $this->assertEquals('Name', $column->refresh()->name);
    }
}
