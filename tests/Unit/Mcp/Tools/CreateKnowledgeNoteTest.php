<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateKnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = MonicaServer::actingAs($user)->tool(CreateKnowledgeNote::class, ['vault' => $vault->slug, 'title' => 'Fire drill', 'body' => 'Dwight']);

        $response->assertOk();
        $this->assertDatabaseHas('knowledge_notes', ['vault_id' => $vault->id, 'title' => 'Fire drill', 'body' => 'Dwight']);
    }
}
