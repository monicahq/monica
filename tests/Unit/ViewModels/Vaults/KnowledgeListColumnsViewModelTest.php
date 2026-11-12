<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeListColumnsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListColumnsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_neighbours_of_each_column_and_lets_one_go_while_another_stays(): void
    {
        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City', 'position' => 2]);

        $columns = new KnowledgeListColumnsViewModel(user: $user, list: $list)->columns();

        $this->assertEquals(['Name', 'City'], array_column($columns, 'name'));
        $this->assertTrue($columns[0]['isFirst']);
        $this->assertEquals('City', $columns[0]['next']);
        $this->assertTrue($columns[1]['canDelete']);
        $this->assertEquals('Remove the column “City”?', $columns[1]['deleteTitle']);
    }
}
