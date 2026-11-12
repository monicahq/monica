<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateKnowledgeList;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_list_with_a_name_and_a_note_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $list = new CreateKnowledgeList(
            user: $user,
            vault: $vault,
            folder: $folder,
            title: 'Favourite restaurants',
            description: 'Places worth going back to.',
        )->execute();

        $this->assertInstanceOf(KnowledgeList::class, $list);
        $this->assertDatabaseHas('knowledge_lists', [
            'id' => $list->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'knowledge_folder_id' => $folder->id,
            'title' => 'Favourite restaurants',
            'description' => 'Places worth going back to.',
        ]);
        $this->assertEquals(['Name', 'Note'], $list->columns()->pluck('name')->all());
        $this->assertEquals([1, 2], $list->columns()->pluck('position')->all());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeListCreation
                && $job->parameters === ['name' => 'Favourite restaurants'],
        );
    }

    #[Test]
    public function a_vault_without_a_folder_gets_general_and_an_untitled_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $list = new CreateKnowledgeList(user: $user, vault: $vault)->execute();

        $this->assertEquals('Untitled list', $list->title);
        $this->assertEquals('General', $list->folder->name);
    }

    #[Test]
    public function it_refuses_a_description_too_long(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new CreateKnowledgeList(user: $user, vault: Vault::factory()->create(['account_id' => $user->account_id]), description: str_repeat('a', 5001))->execute();
    }

    #[Test]
    public function it_refuses_a_folder_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeList(user: $user, vault: $vault, folder: $folder)->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeList(user: User::factory()->create(), vault: Vault::factory()->create())->execute();
    }
}
