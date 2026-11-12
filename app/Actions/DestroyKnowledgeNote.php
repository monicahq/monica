<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a note of a vault's Knowledge for good.
 */
class DestroyKnowledgeNote
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeNote $note,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->note->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->note->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This note is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeNoteDeletion,
            parameters: ['name' => $this->note->title],
            loggable: $this->note,
        )->onQueue('low');
    }
}
