<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Helpers\CountryCodes;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Rename a choice, or change how it is drawn.
 */
class UpdateValueListItem
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
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
        $this->update();
        $this->resendTheRecordsHoldingIt();
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
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }

        if ($this->countryCode !== null && $this->item->valueList->key !== ValueListEnum::Countries->value) {
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
            ->where('value_list_id', $this->item->valueList->id)
            ->whereKeyNot($this->item->id)
            ->where('is_active', true)
            ->get()
            ->contains(fn (ValueListItem $item): bool => $item->countryCode() === $this->countryCode);

        if ($isAlreadyOnTheList) {
            throw ValidationException::withMessages([
                'country_code' => __('This country is already on the list.'),
            ]);
        }
    }

    /**
     * A record shows the label of the item it holds, so a new label is a new text to be found by.
     */
    private function resendTheRecordsHoldingIt(): void
    {
        if (! array_key_exists('label', $this->changes)) {
            return;
        }

        $holders = FieldValue::query()
            ->where('account_id', $this->item->account_id)
            ->where(function (Builder $query): void {
                $query->where('value_list_item_id', $this->item->id)
                    ->orWhereJsonContains('value_list_item_ids', $this->item->id);
            })
            ->get(['valuable_type', 'valuable_id']);

        foreach ([Contact::class, Entity::class, LifeEvent::class] as $type) {
            $ids = $holders->where('valuable_type', $type)->pluck('valuable_id')->unique()->all();

            if ($ids !== []) {
                $type::sendToSearch($type::query()->whereKey($ids));
            }
        }
    }

    private function update(): void
    {
        $before = [
            'label' => $this->item->label,
            'description' => $this->item->description,
            'colour' => $this->item->colour,
            'icon' => $this->item->icon,
            'country_code' => $this->item->country_code,
        ];

        $this->item->label = $this->label;
        $this->item->label_translation_key = null;
        $this->item->description = $this->description;
        $this->item->colour = $this->colour;
        $this->item->icon = $this->icon;
        $this->item->country_code = $this->countryCode;
        $this->item->save();

        foreach ($before as $field => $value) {
            if ($value !== $this->item->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->item->$field];
            }
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListItemUpdate,
            parameters: ['label' => $this->item->label, 'name' => $this->item->valueList->name],
            loggable: $this->item,
            changes: $this->changes,
        );
    }
}
