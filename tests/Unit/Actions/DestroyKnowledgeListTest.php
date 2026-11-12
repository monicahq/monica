<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyKnowledgeList;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyKnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_list_with_its_columns_and_its_items(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Paper clients',
        ]);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        $cell = KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $column->id, 'value' => 'Blue Cross']);

        new DestroyKnowledgeList(user: $user, list: $list)->execute();

        $this->assertModelMissing($list);
        $this->assertModelMissing($column);
        $this->assertModelMissing($item);
        $this->assertModelMissing($cell);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListDeletion
                && $job->parameters === ['name' => 'Paper clients'],
        );
    }

    #[Test]
    public function it_refuses_a_list_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeList(user: User::factory()->create(), list: KnowledgeList::factory()->create())->execute();
    }
}
