<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyKnowledgeListColumn;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyKnowledgeListColumnTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_column_with_its_cells(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1, 'name' => 'Name']);
        $note = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2, 'name' => 'Note']);
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 3, 'name' => 'City']);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        $cell = KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $note->id]);

        new DestroyKnowledgeListColumn(user: $user, column: $note)->execute();

        $this->assertModelMissing($note);
        $this->assertModelMissing($cell);
        $this->assertEquals([$name->id, $city->id], $list->columns()->pluck('id')->all());
        $this->assertEquals([1, 2], $list->columns()->pluck('position')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListColumnDeletion
                && $job->parameters === ['label' => 'Note', 'name' => 'Favourite restaurants'],
        );
    }

    #[Test]
    public function it_removes_an_item_left_with_nothing_written_in_any_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = $this->listFor($user);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);
        $note = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2]);
        $cooperSmith = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);
        $chilis = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $cooperSmith->id, 'knowledge_list_column_id' => $note->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $chilis->id, 'knowledge_list_column_id' => $name->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $chilis->id, 'knowledge_list_column_id' => $note->id]);

        new DestroyKnowledgeListColumn(user: $user, column: $note)->execute();

        $this->assertModelMissing($cooperSmith);
        $this->assertEquals([$chilis->id], $list->items()->pluck('id')->all());
        $this->assertSame(1, $chilis->refresh()->position);
    }

    #[Test]
    public function it_refuses_the_last_column(): void
    {
        $user = User::factory()->create();
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $this->listFor($user)->id]);

        $this->expectException(AuthorizationException::class);

        new DestroyKnowledgeListColumn(user: $user, column: $column)->execute();
    }

    #[Test]
    public function it_refuses_a_column_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeListColumn(user: User::factory()->create(), column: KnowledgeListColumn::factory()->create())->execute();
    }

    private function listFor(User $user): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Favourite restaurants',
        ]);
    }
}
