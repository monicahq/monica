<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeFolderTreeBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeFolderTreeBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_folders_in_order_with_their_files_by_title(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Travel', 'position' => 2]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home', 'position' => 1]);
        $paint = KnowledgeList::factory()->create(['knowledge_folder_id' => $home->id, 'title' => 'Paint colours']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $home->id, 'title' => 'Boiler']);

        $folders = new KnowledgeFolderTreeBlock(user: $user, vault: $vault, current: "list:{$paint->uuid}")->folders();

        $this->assertEquals(['Home', 'Travel'], array_column($folders, 'name'));
        $this->assertEquals(2, $folders[0]['count']);
        $this->assertEquals(['Boiler', 'Paint colours'], array_column($folders[0]['documents'], 'title'));
        $this->assertEquals(['note', 'list'], array_column($folders[0]['documents'], 'kind'));
        $this->assertEquals([false, true], array_column($folders[0]['documents'], 'isCurrent'));
        $this->assertTrue($folders[1]['isEmpty']);
        $this->assertEquals('Home', $folders[1]['previous']);
        $this->assertEquals([['id' => $travel->uuid, 'name' => 'Travel']], $folders[0]['destinations']);
        $this->assertEquals('Choose where its 2 notes and lists go. Deleted with the folder, they cannot be recovered.', $folders[0]['deleteConsequence']);
        $this->assertEquals('The folder is empty. Nothing else is removed.', $folders[1]['deleteConsequence']);
    }

    #[Test]
    public function the_last_folder_says_why_it_stays(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $folder = new KnowledgeFolderTreeBlock(user: $user, vault: $vault)->folders()[0];

        $this->assertFalse($folder['canDelete']);
        $this->assertEquals('The last folder cannot be deleted. Knowledge needs at least one.', $folder['cannotDeleteReason']);
    }

    #[Test]
    public function while_searching_it_keeps_only_what_matches(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Fire drill']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Dundies']);

        $tree = new KnowledgeFolderTreeBlock(user: $user, vault: $vault, search: 'fire');

        $this->assertTrue($tree->isSearching());
        $this->assertEquals(['Office'], array_column($tree->folders(), 'name'));
        $this->assertEquals(['Fire drill'], array_column($tree->folders()[0]['documents'], 'title'));
    }

    #[Test]
    public function a_new_file_goes_to_the_folder_being_read_else_the_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertEquals('A first folder is created with it.', new KnowledgeFolderTreeBlock(user: $user, vault: $vault)->targetLine());

        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home', 'position' => 1]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Travel', 'position' => 2]);

        $this->assertEquals('Added to Home', new KnowledgeFolderTreeBlock(user: $user, vault: $vault)->targetLine());
        $this->assertEquals('Added to Travel', new KnowledgeFolderTreeBlock(user: $user, vault: $vault, currentFolder: $travel)->targetLine());
    }
}
