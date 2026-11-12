<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyKnowledgeNote;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyKnowledgeNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'title' => 'Scott Tots',
        ]);

        new DestroyKnowledgeNote(user: $user, note: $note)->execute();

        $this->assertModelMissing($note);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeNoteDeletion
                && $job->parameters === ['name' => 'Scott Tots'],
        );
    }

    #[Test]
    public function it_refuses_a_note_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyKnowledgeNote(user: User::factory()->create(), note: KnowledgeNote::factory()->create())->execute();
    }
}
