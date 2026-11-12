<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeFolder;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Warehouse']);

        new UpdateKnowledgeFolder(user: $user, folder: $folder, name: 'Annex')->execute();

        $this->assertDatabaseHas('knowledge_folders', ['id' => $folder->id, 'name' => 'Annex']);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeFolderUpdate
                && $job->parameters === ['name' => 'Annex'],
        );
    }

    #[Test]
    public function a_blank_name_keeps_the_previous_one_and_writes_nothing_in_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Warehouse']);

        new UpdateKnowledgeFolder(user: $user, folder: $folder, name: ' ')->execute();

        $this->assertDatabaseHas('knowledge_folders', ['id' => $folder->id, 'name' => 'Warehouse']);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_a_folder_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeFolder(user: User::factory()->create(), folder: KnowledgeFolder::factory()->create(), name: 'Annex')->execute();
    }
}
