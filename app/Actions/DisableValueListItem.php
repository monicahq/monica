<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering a choice, without touching a single record that already uses
 * it.
 *
 * This is the required behaviour of section 3.3, not a way of dodging a
 * deletion: the item disappears from every picker and every stored value that
 * points at it stays exactly as readable as it was. Its place in the list is
 * kept, so offering it again puts it back where it was.
 */
class DisableValueListItem
{
    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
    ) {}

    public function execute(): ValueListItem
    {
        $this->validate();
        $this->disable();
        $this->log();

        return $this->item;
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }
    }

    private function disable(): void
    {
        $this->item->is_active = false;
        $this->item->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemDisabled,
            parameters: ['label' => $this->item->label, 'name' => $this->item->valueList->name],
            loggable: $this->item,
        )->onQueue('low');
    }
}
