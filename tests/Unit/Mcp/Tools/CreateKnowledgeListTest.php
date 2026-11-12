<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateKnowledgeList;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = MonicaServer::actingAs($user)->tool(CreateKnowledgeList::class, ['vault' => $vault->slug, 'title' => 'Clients']);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_lists', ['vault_id' => $vault->id, 'title' => 'Clients']);
    }
}
