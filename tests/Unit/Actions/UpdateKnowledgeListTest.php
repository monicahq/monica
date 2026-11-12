<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeList;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_title_the_description_and_the_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);
        $sales = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        new UpdateKnowledgeList(user: $user, list: $list, title: 'Paper clients', description: 'Who buys from us.', folder: $sales)->execute();

        $this->assertDatabaseHas('knowledge_lists', [
            'id' => $list->id,
            'title' => 'Paper clients',
            'description' => 'Who buys from us.',
            'knowledge_folder_id' => $sales->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListUpdate
                && $job->parameters === ['name' => 'Paper clients'],
        );
    }

    #[Test]
    public function an_empty_description_clears_it_and_a_blank_title_keeps_the_previous_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Paper clients',
            'description' => 'Who buys from us.',
        ]);

        new UpdateKnowledgeList(user: $user, list: $list, title: '', description: '')->execute();

        $this->assertDatabaseHas('knowledge_lists', ['id' => $list->id, 'title' => 'Paper clients', 'description' => null]);
    }

    #[Test]
    public function it_refuses_a_folder_of_another_vault(): void
    {
        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeList(user: $user, list: $list, folder: $folder)->execute();
    }

    #[Test]
    public function it_refuses_a_list_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeList(user: User::factory()->create(), list: KnowledgeList::factory()->create(), title: 'Stolen')->execute();
    }
}
