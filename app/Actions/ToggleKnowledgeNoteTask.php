<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\KnowledgeMarkdown;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Tick or clear one task of a note while reading it. The task is named by its number in reading
 * order and the hash of the text it was read in, so a note changed since it was opened is refused
 * rather than having another task ticked. Only the line the task was written on changes.
 */
class ToggleKnowledgeNoteTask
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeNote $note,
        private readonly int $task,
        private readonly string $bodyHash,
    ) {}

    public function execute(): KnowledgeNote
    {
        $this->validate();
        $this->toggle();
        $this->log();

        return $this->note;
    }

    private function validate(): void
    {
        if ($this->note->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This note is in another account.');
        }

        if (! hash_equals(self::hash($this->note->body), $this->bodyHash)) {
            throw ValidationException::withMessages(['task' => __('This note changed since you opened it. Tick the task again.')]);
        }
    }

    public static function hash(?string $body): string
    {
        return hash('sha256', (string) $body);
    }

    private function toggle(): void
    {
        $line = KnowledgeMarkdown::taskLine($this->note->body, $this->task);
        $parts = preg_split('/(\R)/', (string) $this->note->body, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $index = $line === null ? null : ($line - 1) * 2;

        if ($index === null || ! isset($parts[$index])) {
            throw ValidationException::withMessages(['task' => __('This task is no longer in the note.')]);
        }

        $parts[$index] = (string) preg_replace_callback(
            '/^([>\s]*(?:(?:[-+*]|\d+[.)])\s+)+)\[([ xX])\]/',
            fn (array $matches): string => $matches[1].($matches[2] === ' ' ? '[x]' : '[ ]'),
            $parts[$index],
            1,
            $replacements,
        );

        if ($replacements === 0) {
            throw ValidationException::withMessages(['task' => __('This task is no longer in the note.')]);
        }

        $this->note->body = implode('', $parts);
        $this->note->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeNoteUpdate,
            parameters: ['name' => $this->note->title],
            loggable: $this->note,
        )->onQueue('low');
    }
}
