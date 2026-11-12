<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListItem;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_vault_with_folders_but_no_note_or_list_is_still_empty(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue(new KnowledgeViewModel(user: $user, vault: $vault)->isEmpty());
    }

    #[Test]
    public function the_contents_list_every_folder_with_its_files_and_the_headings_of_each_note(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office', 'position' => 1]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home', 'position' => 2]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Dundies', 'body' => "## Best dressed\n## Hottest in the office"]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Clients']);
        KnowledgeListItem::factory()->count(3)->create(['knowledge_list_id' => $list->id]);

        $viewModel = new KnowledgeViewModel(user: $user, vault: $vault);
        $contents = $viewModel->contents();

        $this->assertFalse($viewModel->isEmpty());
        $this->assertEquals(['Office', 'Home'], array_column($contents, 'name'));
        $this->assertEquals('2 notes and lists', $contents[0]['count']);
        $this->assertTrue($contents[1]['isEmpty']);
        $this->assertEquals(['Clients', 'Dundies'], array_column($contents[0]['documents'], 'title'));
        $this->assertEquals('List of 3 items', $contents[0]['documents'][0]['kind']);
        $this->assertEquals('Note', $contents[0]['documents'][1]['kind']);
        $this->assertEquals([
            ['text' => 'Best dressed', 'url' => route('knowledge.notes.show', [$vault, $note]).'#section-best-dressed'],
            ['text' => 'Hottest in the office', 'url' => route('knowledge.notes.show', [$vault, $note]).'#section-hottest-in-the-office'],
        ], $contents[0]['documents'][1]['headings']);
        $this->assertEquals('2 folders, 1 note, 1 list', $viewModel->summary());
    }
}
