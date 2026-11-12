<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateKnowledgeListItem;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
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

class CreateKnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_item_with_a_text_per_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name']);
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City']);
        $note = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Note']);
        KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);

        $item = new CreateKnowledgeListItem(user: $user, list: $list, cells: [
            $name->uuid => 'Cooper\'s Seafood',
            $city->uuid => 'Scranton',
            $note->uuid => '',
        ])->execute();

        $this->assertInstanceOf(KnowledgeListItem::class, $item);
        $this->assertDatabaseHas('knowledge_list_items', ['id' => $item->id, 'vault_id' => $list->vault_id, 'position' => 2]);
        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $city->id, 'value' => 'Scranton']);
        $this->assertDatabaseMissing('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $note->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListItemCreation
                && $job->parameters === ['name' => 'Favourite restaurants'],
        );
    }

    #[Test]
    public function it_refuses_an_item_with_every_cell_blank(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->expectException(ValidationException::class);

        new CreateKnowledgeListItem(user: $user, list: $list, cells: [$name->uuid => '  '])->execute();
    }

    #[Test]
    public function a_cell_holding_zero_is_something_written(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = $this->listFor($user);
        $sales = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Sales lost']);

        $item = new CreateKnowledgeListItem(user: $user, list: $list, cells: [$sales->uuid => '0'])->execute();

        $this->assertDatabaseHas('knowledge_list_cells', ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $sales->id, 'value' => '0']);
    }

    #[Test]
    public function it_refuses_a_cell_too_long(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->expectException(ValidationException::class);

        new CreateKnowledgeListItem(user: $user, list: $list, cells: [$name->uuid => str_repeat('a', 5001)])->execute();
    }

    #[Test]
    public function it_refuses_a_column_of_another_list(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        $elsewhere = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $this->listFor($user)->id]);

        $this->expectException(ValidationException::class);

        new CreateKnowledgeListItem(user: $user, list: $list, cells: [$elsewhere->uuid => 'Scranton'])->execute();
    }

    #[Test]
    public function it_refuses_a_list_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeListItem(user: User::factory()->create(), list: KnowledgeList::factory()->create(), cells: [])->execute();
    }

    private function listFor(User $user): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Favourite restaurants',
        ]);
    }
}
