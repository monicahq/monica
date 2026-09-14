<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Add a choice to a list.
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

        $this->user->authorize('addItem', $this->valueList);
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
