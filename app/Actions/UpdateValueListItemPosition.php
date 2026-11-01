<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a choice inside its list, so the ones picked most often come first.
 */
class UpdateValueListItemPosition
{
    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
        private readonly int $position,
    ) {}

    public function execute(): ValueListItem
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->item;
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }
    }

    private function update(): void
    {
        $items = ValueListItem::query()
            ->where('value_list_id', $this->item->value_list_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (ValueListItem $item): bool => $item->is($this->item))
            ->values();

        $items->splice(max(0, $this->position - 1), 0, [$this->item]);

        DB::transaction(function () use ($items): void {
            $items->each(function (ValueListItem $item, int $index): void {
                $item->position = $index + 1;
                $item->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemPositionUpdate,
            parameters: ['label' => $this->item->label, 'name' => $this->item->valueList->name],
            loggable: $this->item,
        );
    }
}
