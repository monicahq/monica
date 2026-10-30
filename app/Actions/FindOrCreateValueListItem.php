<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ValueListItemIsStopped;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Find the choice of a list a wording names, ignoring case and surrounding spaces, or add it to
 * the list. A choice that is no longer offered is never offered again this way.
 */
class FindOrCreateValueListItem
{
    /** @param array<int, int> $heldItemIds */
    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
        private readonly string $wording,
        private readonly array $heldItemIds = [],
    ) {}

    public function execute(): ValueListItem
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        $wording = mb_trim($this->wording);

        $match = ValueListItem::query()
            ->where('value_list_id', $this->valueList->id)
            ->orderBy('position')
            ->get()
            ->first(fn (ValueListItem $item): bool => mb_strtolower($item->label) === mb_strtolower($wording));

        if ($match === null) {
            return new CreateValueListItem(
                user: $this->user,
                valueList: $this->valueList,
                label: $wording,
            )->execute();
        }

        if (! $match->is_active && ! in_array($match->id, $this->heldItemIds, true)) {
            throw new ValueListItemIsStopped(__('“:label” is no longer offered. Offer it again from the value lists in your settings.', ['label' => $match->label]));
        }

        return $match;
    }
}
