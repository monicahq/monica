<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\DefaultValues;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;

/**
 * What the default values screen shows: every list the account draws its
 * choices from, and inside each of them the choices themselves, in the order
 * they are offered in.
 *
 * A choice nothing points at can be deleted outright. One that records already
 * use cannot, and is stopped from being offered instead, which is why the two
 * counts below travel with every row. A list the product supplied cannot be
 * deleted at all, which is what `canBeDeleted` says on the list itself.
 */
class DefaultValuesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * The lists of the account, each with the choices it offers.
     *
     * @return array<int, array{id: int, name: string, description: ?string, allowsUserValues: bool, canBeDeleted: bool, countLabel: string, values: array<int, array{id: int, label: string, isActive: bool, canBeDeleted: bool, usageLabel: string}>}>
     */
    public function lists(): array
    {
        return ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->with(['items' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->map(fn (ValueList $list): array => [
                'id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'allowsUserValues' => $list->allows_user_values,
                'canBeDeleted' => ! $list->is_system,
                'countLabel' => $this->countLabel($list->items->count()),
                'values' => $list->items
                    ->map(fn (ValueListItem $item): array => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'isActive' => $item->is_active,
                        'canBeDeleted' => $item->usage_count === 0,
                        'usageLabel' => $this->usageLabel($item),
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * The line beside the name of a list, so nobody has to count the rows.
     */
    private function countLabel(int $count): string
    {
        if ($count === 0) {
            return __('no values');
        }

        if ($count === 1) {
            return __('1 value');
        }

        return __(':count values', ['count' => $count]);
    }

    /**
     * How much a choice is leaned on, which is what tells somebody whether
     * deleting it is free or whether it has to be retired instead.
     */
    private function usageLabel(ValueListItem $item): string
    {
        if ($item->usage_count === 0) {
            return __('not used yet');
        }

        if ($item->usage_count === 1) {
            return __('used once');
        }

        return __('used :count times', ['count' => $item->usage_count]);
    }
}
