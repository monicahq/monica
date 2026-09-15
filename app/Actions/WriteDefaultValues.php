<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Give a record the answers its structure says it starts with.
 */
class WriteDefaultValues
{
    private int $written = 0;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
    ) {}

    public function execute(): int
    {
        DB::transaction(function (): void {
            foreach ($this->fields() as $field) {
                $this->write($field);
            }
        });

        return $this->written;
    }

    /** @return Collection<int, Field> */
    private function fields(): Collection
    {
        return $this->record->structureVersion->assembly
            ->flatMap(fn ($row): Collection => $row->sectionVersion->fields)
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->filter(fn (Field $field): bool => $field->default_value !== null)
            ->values();
    }

    private function write(Field $field): void
    {
        $default = $field->default_value;

        $value = is_array($default) ? $default : [$default];

        if ($value === [] || $value[0] === null) {
            return;
        }

        $item = $this->chosenItem($field, $value[0]);
        $items = $this->chosenItems($field, $value);

        if ($field->type->usesValueList() && $item === null && $items === []) {
            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $this->record,
            field: $field,
            value: $field->type->usesValueList() ? null : $value[0],
            valueListItem: $item,
            valueListItems: $items,
        )->execute();

        $this->written++;
    }

    private function chosenItem(Field $field, mixed $given): ?ValueListItem
    {
        if ($field->type !== FieldTypeEnum::Select && $field->type !== FieldTypeEnum::Radio) {
            return null;
        }

        return ValueListItem::query()
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true)
            ->find($given);
    }

    /**
     * @param  array<int, mixed>  $given
     * @return list<ValueListItem>
     */
    private function chosenItems(Field $field, array $given): array
    {
        if ($field->type !== FieldTypeEnum::Multiselect) {
            return [];
        }

        return ValueListItem::query()
            ->whereIn('id', $given)
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->all();
    }
}
