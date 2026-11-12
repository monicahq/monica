<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\ToggleKnowledgeNoteTask;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeNoteTaskControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ticks_a_task_of_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'body' => '- [ ] Buy trophies',
        ]);

        $this->actingAs($user)->put(route('knowledge.noteTasks.update', [$vault, $note]), ['task' => 1, 'body_hash' => ToggleKnowledgeNoteTask::hash('- [ ] Buy trophies')])
            ->assertRedirect(route('knowledge.notes.show', [$vault, $note]));

        $this->assertEquals('- [x] Buy trophies', $note->refresh()->body);
    }

    #[Test]
    public function it_refuses_a_task_number_that_is_not_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->put(route('knowledge.noteTasks.update', [$vault, $note]), ['task' => 'first'])
            ->assertSessionHasErrors(['task', 'body_hash']);
    }
}
