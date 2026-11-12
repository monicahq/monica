<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeNote;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_title_the_text_and_the_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Draft',
        ]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Travel']);

        $note = new UpdateKnowledgeNote(user: $user, note: $note, title: 'Nashua trip', body: '- [ ] Hotel', folder: $travel)->execute();

        $this->assertDatabaseHas('knowledge_notes', [
            'id' => $note->id,
            'title' => 'Nashua trip',
            'body' => '- [ ] Hotel',
            'knowledge_folder_id' => $travel->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeNoteUpdate
                && $job->parameters === ['name' => 'Nashua trip'],
        );
    }

    #[Test]
    public function what_is_not_given_stays_and_a_blank_title_keeps_the_previous_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Dundies speech',
            'body' => 'Best dressed',
        ]);

        new UpdateKnowledgeNote(user: $user, note: $note, title: '  ')->execute();

        $this->assertDatabaseHas('knowledge_notes', ['id' => $note->id, 'title' => 'Dundies speech', 'body' => 'Best dressed']);
    }

    #[Test]
    public function an_empty_text_clears_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'body' => 'Best dressed',
        ]);

        new UpdateKnowledgeNote(user: $user, note: $note, body: '')->execute();

        $this->assertNull($note->refresh()->body);
    }

    #[Test]
    public function it_refuses_a_folder_of_another_vault(): void
    {
        $user = User::factory()->create();
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeNote(user: $user, note: $note, folder: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_a_note_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeNote(user: User::factory()->create(), note: KnowledgeNote::factory()->create(), title: 'Stolen')->execute();
    }
}
