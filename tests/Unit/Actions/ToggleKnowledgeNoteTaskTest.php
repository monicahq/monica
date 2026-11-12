<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ToggleKnowledgeNoteTask;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ToggleKnowledgeNoteTaskTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ticks_a_task_and_leaves_the_code_block_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = $this->noteFor($user, "```\n- [ ] not a task\n```\n\n- [x] Flights\n- [ ] Hotel");

        new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 2, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();

        $this->assertEquals("```\n- [ ] not a task\n```\n\n- [x] Flights\n- [x] Hotel", $note->refresh()->body);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::KnowledgeNoteUpdate,
        );
    }

    #[Test]
    public function it_clears_a_ticked_task(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = $this->noteFor($user, '1. [X] Call Jan');

        new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 1, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();

        $this->assertEquals('1. [ ] Call Jan', $note->refresh()->body);
    }

    #[Test]
    public function it_refuses_a_task_no_longer_in_the_note(): void
    {
        $user = User::factory()->create();
        $note = $this->noteFor($user, '- [ ] Hotel');

        $this->expectException(ValidationException::class);

        new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 2, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();
    }

    #[Test]
    public function it_refuses_a_note_changed_since_it_was_opened(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $openedBody = "- [ ] Flights\n- [ ] Hotel";
        $note = $this->noteFor($user, "- [ ] Rent a car\n{$openedBody}");

        try {
            new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 2, bodyHash: ToggleKnowledgeNoteTask::hash($openedBody))->execute();
            $this->fail('A note changed since it was opened was toggled.');
        } catch (ValidationException) {
            $this->assertEquals("- [ ] Rent a car\n- [ ] Flights\n- [ ] Hotel", $note->refresh()->body);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_ticks_a_task_written_after_two_list_markers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = $this->noteFor($user, '- - [ ] Order the Dundie trophies');

        new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 1, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();

        $this->assertEquals('- - [x] Order the Dundie trophies', $note->refresh()->body);
    }

    #[Test]
    public function it_keeps_the_line_endings_the_note_was_written_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = $this->noteFor($user, "Party planning\r\n\r\n> - [ ] Streamers\r\n> - [ ] Cake");

        new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 2, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();

        $this->assertEquals("Party planning\r\n\r\n> - [ ] Streamers\r\n> - [x] Cake", $note->refresh()->body);
    }

    #[Test]
    public function it_refuses_a_task_whose_box_is_not_on_the_line_it_starts_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $note = $this->noteFor($user, "-\n  [ ] Call the warehouse");

        try {
            new ToggleKnowledgeNoteTask(user: $user, note: $note, task: 1, bodyHash: ToggleKnowledgeNoteTask::hash($note->body))->execute();
            $this->fail('A task that could not be ticked was reported as ticked.');
        } catch (ValidationException) {
            $this->assertEquals("-\n  [ ] Call the warehouse", $note->refresh()->body);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_a_note_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ToggleKnowledgeNoteTask(user: User::factory()->create(), note: KnowledgeNote::factory()->create(['body' => '- [ ] Hotel']), task: 1, bodyHash: ToggleKnowledgeNoteTask::hash('- [ ] Hotel'))->execute();
    }

    private function noteFor(User $user, string $body): KnowledgeNote
    {
        return KnowledgeNote::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
            'body' => $body,
        ]);
    }
}
