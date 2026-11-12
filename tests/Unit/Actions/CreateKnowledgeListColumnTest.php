<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateKnowledgeListColumn;
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
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeListColumnTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_column_after_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = $this->listFor($user);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);

        $column = new CreateKnowledgeListColumn(user: $user, list: $list, name: 'Cuisine')->execute();

        $this->assertDatabaseHas('knowledge_list_columns', [
            'id' => $column->id,
            'account_id' => $user->account_id,
            'vault_id' => $list->vault_id,
            'knowledge_list_id' => $list->id,
            'name' => 'Cuisine',
            'position' => 2,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListColumnCreation
                && $job->parameters === ['label' => 'Cuisine', 'name' => 'Favourite restaurants'],
        );
    }

    #[Test]
    public function it_refuses_a_blank_name(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new CreateKnowledgeListColumn(user: $user, list: $this->listFor($user), name: ' ')->execute();
    }

    #[Test]
    public function it_refuses_a_list_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeListColumn(user: User::factory()->create(), list: KnowledgeList::factory()->create(), name: 'City')->execute();
    }

    private function listFor(User $user): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Favourite restaurants',
        ]);
    }
}
