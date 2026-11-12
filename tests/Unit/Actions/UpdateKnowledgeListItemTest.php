<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeListItem;
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
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_cells_given_and_empties_a_blank_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$item, $name, $city] = $this->itemFor($user);

        new UpdateKnowledgeListItem(user: $user, item: $item, cells: [$city->uuid => 'Nashua'])->execute();

        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $city->id, 'value' => 'Nashua']);
        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Cooper\'s Seafood']);

        new UpdateKnowledgeListItem(user: $user, item: $item, cells: [$city->uuid => ''])->execute();

        $this->assertDatabaseMissing('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $city->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListItemUpdate,
        );
    }

    #[Test]
    public function it_refuses_to_leave_every_cell_blank(): void
    {
        $user = User::factory()->create();
        [$item, $name, $city] = $this->itemFor($user);

        $this->expectException(ValidationException::class);

        new UpdateKnowledgeListItem(user: $user, item: $item, cells: [$name->uuid => '', $city->uuid => ''])->execute();
    }

    #[Test]
    public function it_refuses_an_item_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeListItem(user: User::factory()->create(), item: KnowledgeListItem::factory()->create(), cells: [])->execute();
    }

    /** @return array{KnowledgeListItem, KnowledgeListColumn, KnowledgeListColumn} */
    private function itemFor(User $user): array
    {
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name']);
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City']);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Cooper\'s Seafood']);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $city->id, 'value' => 'Scranton']);

        return [$item, $name, $city];
    }
}
