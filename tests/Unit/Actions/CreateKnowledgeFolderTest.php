<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateKnowledgeFolder;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateKnowledgeFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_folder_after_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);

        $folder = new CreateKnowledgeFolder(user: $user, vault: $vault, name: ' <b>Paper sales</b> ')->execute();

        $this->assertInstanceOf(KnowledgeFolder::class, $folder);
        $this->assertDatabaseHas('knowledge_folders', [
            'id' => $folder->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Paper sales',
            'position' => 2,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeFolderCreation
                && $job->parameters === ['name' => 'Paper sales'],
        );
    }

    #[Test]
    public function it_refuses_a_blank_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new CreateKnowledgeFolder(user: $user, vault: $vault, name: '   ')->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateKnowledgeFolder(user: $user, vault: Vault::factory()->create(), name: 'Travel')->execute();
    }
}
