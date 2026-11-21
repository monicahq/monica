<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\ValueLists;

use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;

class ValueListsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?ValueListItem $editingItem = null,
    ) {}

    /**
     * @return array<int, array{id: int, name: string, description: ?string, allowsUserValues: bool, addValueReason: ?string, canBeDeleted: bool, deleteReason: ?string, countLabel: string, values: array<int, array{id: int, label: string, isActive: bool, canBeDeleted: bool, usageLabel: string, isEditing: bool, editUrl: string}>}>
     */
    public function lists(): array
    {
        return ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_shared', true)
            ->with(['items' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->map(fn (ValueList $list): array => [
                'id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'allowsUserValues' => $this->user->can('addItem', $list),
                'addValueReason' => $this->reason('addItem', $list),
                'canBeDeleted' => $this->user->can('delete', $list),
                'deleteReason' => $this->reason('delete', $list),
                'countLabel' => $this->countLabel($list->items->count()),
                'holdsCountries' => $list->key === ValueListEnum::Countries->value,
                'values' => $list->items
                    ->map(fn (ValueListItem $item): array => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'isActive' => $item->is_active,
                        'canBeDeleted' => $this->user->can('delete', $item),
                        'usageLabel' => $this->usageLabel($item),
                        'isEditing' => $this->editingItem?->is($item) ?? false,
                        'editUrl' => route('settings.valueListItems.edit', $item->id),
                        'holdsACountry' => $list->key === ValueListEnum::Countries->value,
                        'countryCode' => $list->key === ValueListEnum::Countries->value ? mb_strtoupper((string) ($item->country_code ?? $item->key)) : null,
                    ])
                    ->all(),
            ])
            ->all();
    }

    public function user(): User
    {
        return $this->user;
    }

    private function reason(string $ability, ValueList $list): ?string
    {
        $response = $this->user->inspect($ability, $list);

        return $response->denied() ? (string) $response->message() : null;
    }

    private function countLabel(int $count): string
    {
        if ($count === 0) {
            return __('no values');
        }

        return trans_choice(':count value|:count values', $count);
    }

    private function usageLabel(ValueListItem $item): string
    {
        if ($item->usage_count === 0) {
            return __('not used yet');
        }

        return trans_choice('{1}used once|used :count times', $item->usage_count);
    }
}
