<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_note_and_opens_its_editor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('knowledge.notes.create', $vault));

        $note = KnowledgeNote::query()->where('vault_id', $vault->id)->firstOrFail();
        $response->assertRedirect(route('knowledge.notes.edit', [$vault, $note]));

        $this->actingAs($user)->get(route('knowledge.notes.edit', [$vault, $note]))
            ->assertOk()
            ->assertSee('Add a link')
            ->assertSee('name="body"', escape: false)
            ->assertSee('x-data="knowledgeNoteEditor()"', escape: false);
    }

    #[Test]
    public function it_shows_a_note_with_its_markdown_drawn(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office'])->id,
            'title' => 'Dundies',
            'body' => "## Awards\n- [ ] Buy trophies\n\n<script>alert(1)</script>",
        ]);

        $this->actingAs($user)->get(route('knowledge.notes.show', [$vault, $note]))
            ->assertOk()
            ->assertSee('Awards')
            ->assertSee('data-task="1"', escape: false)
            ->assertSee('name="body_hash"', escape: false)
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee(route('knowledge.noteTasks.update', [$vault, $note]), escape: false)
            ->assertSee(route('knowledge.noteMarkdown.show', [$vault, $note]), escape: false);
    }

    #[Test]
    public function it_saves_a_note_and_empties_its_text(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'body' => 'Old text',
        ]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->put(route('knowledge.notes.update', [$vault, $note]), [
            'title' => 'Nashua trip',
            'body' => '',
            'folder' => $travel->uuid,
        ])
            ->assertRedirect(route('knowledge.notes.show', [$vault, $note]))
            ->assertSessionHas('status');

        $note->refresh();
        $this->assertEquals('Nashua trip', $note->title);
        $this->assertNull($note->body);
        $this->assertEquals($travel->id, $note->knowledge_folder_id);
    }

    #[Test]
    public function it_refuses_a_title_too_long(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->put(route('knowledge.notes.update', [$vault, $note]), ['title' => str_repeat('a', 256)])
            ->assertSessionHasErrors('title');
    }

    #[Test]
    public function it_deletes_a_note_and_goes_back_to_the_contents(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->delete(route('knowledge.notes.destroy', [$vault, $note]))
            ->assertRedirect(route('knowledge.index', $vault))
            ->assertSessionHas('status');

        $this->assertModelMissing($note);
    }

    #[Test]
    public function a_note_reached_through_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);

        $this->actingAs($user)->get(route('knowledge.notes.show', [$vault, $note]))->assertNotFound();
    }

    #[Test]
    public function a_folder_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->post(route('knowledge.notes.create', $vault), ['folder' => $elsewhere->uuid])->assertNotFound();
        $this->actingAs($user)->put(route('knowledge.notes.update', [$vault, $note]), ['title' => 'Dundies', 'folder' => $elsewhere->uuid])->assertNotFound();

        $this->assertDatabaseCount('knowledge_notes', 1);
    }
}
