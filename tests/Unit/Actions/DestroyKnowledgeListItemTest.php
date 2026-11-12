<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyKnowledgeListItem;
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

class DestroyKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_item_with_its_cells(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Favourite restaurants',
        ]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        $cell = KnowledgeListCell::factory()->create([
            'knowledge_list_item_id' => $item->id,
            'knowledge_list_column_id' => KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id])->id,
        ]);

        new DestroyKnowledgeListItem(user: $user, item: $item)->execute();

        $this->assertModelMissing($item);
        $this->assertModelMissing($cell);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListItemDeletion
                && $job->parameters === ['name' => 'Favourite restaurants'],
        );
    }

    #[Test]
    public function it_refuses_an_item_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeListItem(user: User::factory()->create(), item: KnowledgeListItem::factory()->create())->execute();
    }
}
