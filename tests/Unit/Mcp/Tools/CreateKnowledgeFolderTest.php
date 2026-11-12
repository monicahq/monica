<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateKnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = MonicaServer::actingAs($user)->tool(CreateKnowledgeFolder::class, ['vault' => $vault->slug, 'name' => 'Warehouse']);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_folders', ['vault_id' => $vault->id, 'name' => 'Warehouse']);
    }
}
