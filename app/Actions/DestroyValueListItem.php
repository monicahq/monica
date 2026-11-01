<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Remove a choice from a list, which is only allowed while nothing points at it.
 */
class DestroyValueListItem
{
    private string $label;

    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }

        $this->user->authorize('delete', $this->item);
    }

    private function destroy(): void
    {
        $this->label = $this->item->label;
        $this->name = $this->item->valueList->name;

        $this->item->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemDeletion,
            parameters: ['label' => $this->label, 'name' => $this->name],
            loggable: $this->item,
        );
    }
}
