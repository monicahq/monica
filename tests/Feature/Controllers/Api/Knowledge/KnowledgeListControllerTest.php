<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Knowledge;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => ['title', 'description', 'folder' => ['id', 'name'], 'columns', 'items', 'created_at', 'updated_at'],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_starts_a_list_with_its_two_columns_and_deletes_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $id = $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists", ['title' => 'Favourite restaurants'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.columns.0.name', 'Name')
            ->assertJsonPath('data.attributes.columns.1.name', 'Note')
            ->json('data.id');

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$id}")->assertNoContent();
    }

    #[Test]
    public function it_lists_the_lists_of_the_vault_and_changes_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id, 'title' => 'Clients']);
        KnowledgeList::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/lists")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}", ['description' => 'Who buys from us.'])
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'Clients')
            ->assertJsonPath('data.attributes.description', 'Who buys from us.');
    }

    #[Test]
    public function a_folder_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/lists?folder={$elsewhere->uuid}")->assertNotFound();
        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists", ['folder_id' => $elsewhere->uuid])->assertNotFound();
        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}", ['folder_id' => $elsewhere->uuid])->assertNotFound();

        $this->assertDatabaseCount('knowledge_lists', 1);
    }

    #[Test]
    public function a_list_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}")->assertNotFound();
    }
}
