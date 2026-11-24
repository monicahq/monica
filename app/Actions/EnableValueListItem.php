<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Offer a choice again, in the place it always had.
 */
class EnableValueListItem
{
    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
    ) {}

    public function execute(): ValueListItem
    {
        $this->validate();
        $this->enable();
        $this->log();

        return $this->item;
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }

        if ($this->item->valueList->key !== ValueListEnum::Countries->value) {
            return;
        }

        $countryCode = $this->item->countryCode();

        if ($countryCode === null) {
            return;
        }

        $isAlreadyOnTheList = ValueListItem::query()
            ->where('value_list_id', $this->item->value_list_id)
            ->whereKeyNot($this->item->id)
            ->where('is_active', true)
            ->get()
            ->contains(fn (ValueListItem $item): bool => $item->countryCode() === $countryCode);

        if ($isAlreadyOnTheList) {
            throw ValidationException::withMessages([
                'active' => __('This country is already on the list.'),
            ]);
        }
    }

    private function enable(): void
    {
        $this->item->is_active = true;
        $this->item->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemEnabled,
            parameters: ['label' => $this->item->label, 'name' => $this->item->valueList->name],
            loggable: $this->item,
        );
    }
}
