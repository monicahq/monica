<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete an item of a list of a vault's Knowledge for good, with its cells.
 */
class DestroyKnowledgeListItem
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeListItem $item,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->item->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This item is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListItemDeletion,
            parameters: ['name' => $this->item->list->title],
            loggable: $this->item,
        )->onQueue('low');
    }
}
