<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeListColumn;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeListColumnTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_column_and_a_blank_name_keeps_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Town']);

        new UpdateKnowledgeListColumn(user: $user, column: $column, name: 'City')->execute();
        new UpdateKnowledgeListColumn(user: $user, column: $column, name: '')->execute();

        $this->assertDatabaseHas('knowledge_list_columns', ['id' => $column->id, 'name' => 'City']);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListColumnUpdate,
        );
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_refuses_a_column_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeListColumn(user: User::factory()->create(), column: KnowledgeListColumn::factory()->create(), name: 'City')->execute();
    }
}
