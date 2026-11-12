<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyKnowledgeFolder;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_folder_and_moves_its_notes_and_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1, 'name' => 'Home']);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 2]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $home->id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $home->id]);

        new DestroyKnowledgeFolder(user: $user, folder: $home, moveTo: $travel)->execute();

        $this->assertModelMissing($home);
        $this->assertDatabaseHas('knowledge_notes', ['id' => $note->id, 'knowledge_folder_id' => $travel->id]);
        $this->assertDatabaseHas('knowledge_lists', ['id' => $list->id, 'knowledge_folder_id' => $travel->id]);
        $this->assertDatabaseHas('knowledge_folders', ['id' => $travel->id, 'position' => 1]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeFolderDeletion
                && $job->parameters === ['name' => 'Home'],
        );
    }

    #[Test]
    public function it_deletes_a_folder_with_its_notes_and_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Home']);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $home->id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => $home->id]);

        new DestroyKnowledgeFolder(user: $user, folder: $home)->execute();

        $this->assertModelMissing($note);
        $this->assertModelMissing($list);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeFolderDeletedWithDocuments
                && $job->parameters === ['name' => 'Home', 'count' => 2],
        );
    }

    #[Test]
    public function it_refuses_the_last_folder(): void
    {
        $user = User::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('The last folder cannot be deleted. Knowledge needs at least one.');

        new DestroyKnowledgeFolder(user: $user, folder: $folder)->execute();
    }

    #[Test]
    public function it_refuses_to_move_the_notes_and_lists_to_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeFolder(user: $user, folder: $folder, moveTo: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_to_move_the_notes_and_lists_into_the_folder_being_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ValidationException::class);

        new DestroyKnowledgeFolder(user: $user, folder: $folder, moveTo: $folder)->execute();
    }

    #[Test]
    public function it_refuses_a_folder_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeFolder(user: User::factory()->create(), folder: KnowledgeFolder::factory()->create())->execute();
    }
}
