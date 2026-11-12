<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeNoteViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeNoteViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_note_its_folder_its_drawn_text_and_the_folders_it_could_move_to(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office', 'position' => 1]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home', 'position' => 2]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Dundies', 'body' => '**Best** dressed']);

        $viewModel = new KnowledgeNoteViewModel(user: $user, note: $note);

        $this->assertEquals('Office', $viewModel->folderName());
        $this->assertFalse($viewModel->isEmpty());
        $this->assertEquals("<p><strong>Best</strong> dressed</p>\n", $viewModel->html());
        $this->assertEquals([
            ['id' => $office->uuid, 'name' => 'Office', 'isSelected' => true],
            ['id' => $home->uuid, 'name' => 'Home', 'isSelected' => false],
        ], $viewModel->folders());
        $this->assertEquals('Delete the note “Dundies”?', $viewModel->deleteTitle());
        $this->assertStringStartsWith('Edited ', $viewModel->edited());
    }

    #[Test]
    public function a_note_without_text_is_empty(): void
    {
        $user = User::factory()->create();
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'body' => null,
        ]);

        $this->assertTrue(new KnowledgeNoteViewModel(user: $user, note: $note)->isEmpty());
    }
}
