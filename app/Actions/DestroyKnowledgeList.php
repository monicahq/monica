<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a list of a vault's Knowledge for good, with its columns and its items.
 */
class DestroyKnowledgeList
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->list->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->list->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This list is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListDeletion,
            parameters: ['name' => $this->list->title],
            loggable: $this->list,
        )->onQueue('low');
    }
}
