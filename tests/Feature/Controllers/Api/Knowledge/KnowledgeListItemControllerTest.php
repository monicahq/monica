<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Knowledge;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListItemControllerTest extends TestCase
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
    public function it_adds_changes_and_deletes_an_item(): void
    {
        Queue::fake();

        [$user, $vault, $list] = $this->clients();
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name']);

        Sanctum::actingAs($user);

        $item = $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items", ['cells' => [$name->uuid => 'Cooper\'s Seafood']])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath("data.attributes.items.0.cells.{$name->uuid}", 'Cooper\'s Seafood')
            ->json('data.attributes.items.0.id');

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items/{$item}", ['cells' => [$name->uuid => 'Chili\'s']])
            ->assertOk()
            ->assertJsonPath("data.attributes.items.0.cells.{$name->uuid}", 'Chili\'s');

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items/{$item}")->assertNoContent();
    }

    #[Test]
    public function it_refuses_an_item_with_every_cell_blank(): void
    {
        [$user, $vault, $list] = $this->clients();
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items", ['cells' => [$name->uuid => '']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cells');
    }

    #[Test]
    public function it_refuses_cells_not_keyed_by_a_column_of_the_list(): void
    {
        [$user, $vault, $list] = $this->clients();
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items", ['cells' => ['Harper Collins']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cells.0');
    }

    #[Test]
    public function an_item_of_another_list_is_not_found(): void
    {
        [$user, $vault, $list] = $this->clients();
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        $suppliers = KnowledgeList::factory()->create(['knowledge_folder_id' => $list->knowledge_folder_id]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $suppliers->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => KnowledgeListColumn::factory()->create(['knowledge_list_id' => $suppliers->id])->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items/{$item->uuid}", ['cells' => [$name->uuid => 'Harper Collins']])
            ->assertNotFound();
        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/items/{$item->uuid}")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: KnowledgeList} */
    private function clients(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Clients',
        ]);

        return [$user, $vault, $list];
    }
}
