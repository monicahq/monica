<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateKnowledgeFolder;
use App\Models\KnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);

        $response = MonicaServer::actingAs($user)->tool(UpdateKnowledgeFolder::class, ['vault' => $vault->slug, 'folder' => $folder->uuid, 'name' => 'Annex']);

        $response->assertOk();
        $this->assertEquals('Annex', $folder->refresh()->name);
    }

    #[Test]
    public function it_reports_a_folder_of_another_vault_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Office']);

        MonicaServer::actingAs($user)->tool(UpdateKnowledgeFolder::class, ['vault' => $vault->slug, 'folder' => $folder->uuid, 'name' => 'Annex'])
            ->assertHasErrors();

        $this->assertEquals('Office', $folder->refresh()->name);
    }
}
