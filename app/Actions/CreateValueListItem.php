<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Helpers\CountryCodes;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Add a choice to a list.
 */
class CreateValueListItem
{
    private ValueListItem $item;

    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
        private ?string $label,
        private ?string $description = null,
        private ?string $colour = null,
        private ?string $icon = null,
        private ?string $countryCode = null,
    ) {}

    public function execute(): ValueListItem
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->item;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::nullablePlainText($this->label);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? '') ?: null;
        $this->icon = TextSanitizer::nullablePlainText($this->icon);
        $this->countryCode = mb_strtoupper(TextSanitizer::nullablePlainText($this->countryCode) ?? '') ?: null;
    }

    private function validate(): void
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        $this->user->authorize('addItem', $this->valueList);

        if ($this->countryCode !== null && $this->valueList->key !== ValueListEnum::Countries->value) {
            throw new InvalidArgumentException('Only a country carries a country code.');
        }

        if ($this->label === null && $this->countryCode === null) {
            throw new InvalidArgumentException('A value needs a label, or a country picked from the list.');
        }

        if ($this->countryCode === null) {
            return;
        }

        if (! in_array($this->countryCode, CountryCodes::known(), true)) {
            throw new InvalidArgumentException("{$this->countryCode} is not a country code.");
        }

        $this->refuseACountryAlreadyOnTheList();
    }

    private function refuseACountryAlreadyOnTheList(): void
    {
        $isAlreadyOnTheList = ValueListItem::query()
            ->where('value_list_id', $this->valueList->id)
            ->where('is_active', true)
            ->get()
            ->contains(fn (ValueListItem $item): bool => $item->countryCode() === $this->countryCode);

        if ($isAlreadyOnTheList) {
            throw ValidationException::withMessages([
                'country_code' => __('This country is already on the list.'),
            ]);
        }
    }

    private function create(): void
    {
        $positions = ValueListItem::query()
            ->where('value_list_id', $this->valueList->id)
            ->pluck('position');

        $this->item = $this->valueList->items()->create([
            'account_id' => $this->user->account_id,
            'key' => null,
            'country_code' => $this->countryCode,
            'label' => $this->label,
            'label_translation_key' => null,
            'description' => $this->description,
            'colour' => $this->colour,
            'icon' => $this->icon,
            'position' => ($positions->max() ?? 0) + 1,
            'is_system' => false,
            'is_active' => true,
            'usage_count' => 0,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemCreation,
            parameters: ['label' => $this->item->label, 'name' => $this->valueList->name],
            loggable: $this->item,
        );
    }
}
