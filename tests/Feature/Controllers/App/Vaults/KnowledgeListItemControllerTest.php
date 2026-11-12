<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListItemControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->actingAs($user)->post(route('knowledge.listItems.create', [$vault, $list]), ['cells' => [$name->uuid => 'Cooper\'s Seafood']])
            ->assertRedirect(route('knowledge.lists.show', [$vault, $list]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_column_id' => $name->id, 'value' => 'Cooper\'s Seafood']);
    }

    #[Test]
    public function an_item_with_every_cell_blank_comes_back_as_an_error(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->actingAs($user)->post(route('knowledge.listItems.create', [$vault, $list]), ['cells' => [$name->uuid => '']])
            ->assertSessionHasErrors('cells');
    }

    #[Test]
    public function it_edits_an_item_in_its_row_and_saves_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Poor Richard\'s']);

        $this->actingAs($user)->get(route('knowledge.listItems.edit', [$vault, $list, $item]))
            ->assertOk()
            ->assertSee('name="cells['.$name->uuid.']"', escape: false)
            ->assertSee(route('knowledge.listItems.update', [$vault, $list, $item]), escape: false);

        $this->actingAs($user)->put(route('knowledge.listItems.update', [$vault, $list, $item]), ['cells' => [$name->uuid => 'Chili\'s']])
            ->assertRedirect(route('knowledge.lists.show', [$vault, $list]));

        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'value' => 'Chili\'s']);
    }

    #[Test]
    public function it_deletes_an_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);

        $this->actingAs($user)->delete(route('knowledge.listItems.destroy', [$vault, $list, $item]))
            ->assertRedirect(route('knowledge.lists.show', [$vault, $list]));

        $this->assertModelMissing($item);
    }

    #[Test]
    public function an_item_of_another_list_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $elsewhere = KnowledgeListItem::factory()->create(['knowledge_list_id' => $this->list($vault)->id]);

        $this->actingAs($user)->delete(route('knowledge.listItems.destroy', [$vault, $list, $elsewhere]))->assertNotFound();
    }

    private function list(Vault $vault): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
        ]);
    }
}
