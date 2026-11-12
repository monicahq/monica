<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateKnowledgeFolderPosition;
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

class UpdateKnowledgeFolderPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_folder_and_keeps_the_order_without_a_gap(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);
        $sales = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 2]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 3]);

        new UpdateKnowledgeFolderPosition(user: $user, folder: $travel, position: 1)->execute();

        $this->assertEquals([$travel->id, $home->id, $sales->id], $vault->knowledgeFolders()->pluck('id')->all());
        $this->assertEquals([1, 2, 3], $vault->knowledgeFolders()->pluck('position')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeFolderPositionUpdate,
        );
    }

    #[Test]
    public function a_position_past_the_end_puts_it_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 2]);

        new UpdateKnowledgeFolderPosition(user: $user, folder: $home, position: 9)->execute();

        $this->assertEquals([$travel->id, $home->id], $vault->knowledgeFolders()->pluck('id')->all());
    }

    #[Test]
    public function it_refuses_a_folder_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdateKnowledgeFolderPosition(user: User::factory()->create(), folder: KnowledgeFolder::factory()->create(), position: 1)->execute();
    }
}
