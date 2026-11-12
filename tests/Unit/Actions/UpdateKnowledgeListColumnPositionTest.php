<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeListColumnPosition;
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

class UpdateKnowledgeListColumnPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_column_and_keeps_the_order_without_a_gap(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2]);

        new UpdateKnowledgeListColumnPosition(user: $user, column: $city, position: 1)->execute();

        $this->assertEquals([$city->id, $name->id], $list->columns()->pluck('id')->all());
        $this->assertEquals([1, 2], $list->columns()->pluck('position')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListColumnPositionUpdate,
        );
    }

    #[Test]
    public function it_refuses_a_column_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeListColumnPosition(user: User::factory()->create(), column: KnowledgeListColumn::factory()->create(), position: 1)->execute();
    }
}
