<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeListViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_each_item_one_cell_per_column_in_order(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City', 'position' => 2]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => '**Cooper\'s**']);

        $viewModel = new KnowledgeListViewModel(user: $user, list: $list->refresh());
        $cells = $viewModel->items()[0]['cells'];

        $this->assertEquals(['Name', 'City'], array_column($viewModel->columns(), 'name'));
        $this->assertEquals('<strong>Cooper\'s</strong>', $cells[0]['html']);
        $this->assertTrue($cells[1]['isBlank']);
        $this->assertEquals('1 item', $viewModel->itemCount());
        $this->assertFalse($viewModel->startsAdding());
    }

    #[Test]
    public function a_list_without_items_starts_with_the_row_to_add_one(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(new KnowledgeListViewModel(user: $user, list: $this->listFor($user))->startsAdding());
    }

    #[Test]
    public function the_item_being_edited_is_marked(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);

        $items = new KnowledgeListViewModel(user: $user, list: $list, editingItem: $item)->items();

        $this->assertTrue($items[0]['isEditing']);
    }

    private function listFor(User $user): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
    }
}
