<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeNoteMarkdownControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_note_over_as_a_markdown_file_named_after_its_title(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Dundies 2026: the nominees',
            'body' => "- Pam, for the art show\n- Dwight, for sales",
        ]);

        $response = $this->actingAs($user)->get(route('knowledge.noteMarkdown.show', [$vault, $note]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=utf-8')
            ->assertDownload('dundies-2026-the-nominees.md');

        $this->assertSame("# Dundies 2026: the nominees\n\n- Pam, for the art show\n- Dwight, for sales", $response->streamedContent());
    }

    #[Test]
    public function a_title_without_a_usable_name_gives_a_file_called_note(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => '???',
        ]);

        $this->actingAs($user)->get(route('knowledge.noteMarkdown.show', [$vault, $note]))
            ->assertDownload('note.md');
    }

    #[Test]
    public function a_note_reached_through_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);

        $this->actingAs($user)->get(route('knowledge.noteMarkdown.show', [$vault, $note]))->assertNotFound();
    }
}
