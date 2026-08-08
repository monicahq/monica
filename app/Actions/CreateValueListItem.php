<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ValueListIsClosed;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Add a choice to a list. This is the half of section 5.4 that makes the
 * others worth having: a supplied list is a starting point, and everybody
 * finishes it themselves.
 *
 * A list that does not take values of its own refuses, since its choices mean
 * something to the product and not only to the person reading them.
 */
class CreateValueListItem
{
    private ValueListItem $item;

    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
        private string $label,
        private ?string $description = null,
        private ?string $colour = null,
        private ?string $icon = null,
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
        $this->label = TextSanitizer::plainText($this->label);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? '') ?: null;
        $this->icon = TextSanitizer::nullablePlainText($this->icon);
    }

    private function validate(): void
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        if (! $this->valueList->allows_user_values) {
            throw new ValueListIsClosed('This value list does not take choices of its own.');
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
        )->onQueue('low');
    }
}
