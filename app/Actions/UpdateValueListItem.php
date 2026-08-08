<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a choice, or change how it is drawn.
 *
 * Renaming an item never changes what a published section means, because a
 * published section stores the item it resolved to rather than a pointer to
 * it. Everything reading the list live, on the other hand, reads the new name
 * straight away, which is the point of renaming.
 *
 * Giving a supplied item a label of its own takes it off its translation key,
 * so it stops following the interface language.
 */
class UpdateValueListItem
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly ValueListItem $item,
        private string $label,
        private ?string $description = null,
        private ?string $colour = null,
        private ?string $icon = null,
    ) {}

    public function execute(): ValueListItem
    {
        $this->sanitize();
        $this->validate();
        $this->update();
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
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value is in another account.');
        }
    }

    private function update(): void
    {
        $before = [
            'label' => $this->item->label,
            'description' => $this->item->description,
            'colour' => $this->item->colour,
            'icon' => $this->item->icon,
        ];

        $this->item->label = $this->label;
        $this->item->label_translation_key = null;
        $this->item->description = $this->description;
        $this->item->colour = $this->colour;
        $this->item->icon = $this->icon;
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
        )->onQueue('low');
    }
}
