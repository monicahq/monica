<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateKnowledgeNote;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_note_in_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $note = new CreateKnowledgeNote(
            user: $user,
            vault: $vault,
            folder: $folder,
            title: 'Water shutoff',
            body: "Under the stairs, behind the panel.\n\n<b>Ask Dwight</b>",
        )->execute();

        $this->assertInstanceOf(KnowledgeNote::class, $note);
        $this->assertDatabaseHas('knowledge_notes', [
            'id' => $note->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'knowledge_folder_id' => $folder->id,
            'title' => 'Water shutoff',
            'body' => "Under the stairs, behind the panel.\n\n<b>Ask Dwight</b>",
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeNoteCreation
                && $job->parameters === ['name' => 'Water shutoff'],
        );
    }

    #[Test]
    public function a_vault_without_a_folder_gets_general_to_hold_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $note = new CreateKnowledgeNote(user: $user, vault: $vault)->execute();

        $this->assertEquals('Untitled note', $note->title);
        $this->assertDatabaseHas('knowledge_folders', [
            'id' => $note->knowledge_folder_id,
            'vault_id' => $vault->id,
            'name' => 'General',
            'position' => 1,
        ]);
    }

    #[Test]
    public function without_a_folder_named_it_goes_to_the_first_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 2]);
        $first = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);

        $note = new CreateKnowledgeNote(user: $user, vault: $vault, title: 'Party planning')->execute();

        $this->assertEquals($first->id, $note->knowledge_folder_id);
    }

    #[Test]
    public function it_refuses_a_text_too_long(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new CreateKnowledgeNote(user: $user, vault: $vault, body: str_repeat('a', KnowledgeNote::MAX_BODY_LENGTH + 1))->execute();
    }

    #[Test]
    public function it_refuses_a_folder_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeNote(user: $user, vault: $vault, folder: $folder)->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeNote(user: $user, vault: Vault::factory()->create())->execute();
    }
}
