<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use Illuminate\Validation\ValidationException;

/**
 * The rules adding and changing an item of a Knowledge list share: each text names a column of
 * that list by its uuid, holds at most 5,000 characters, and a blank text is no cell at all. A
 * text of "0" is something written.
 */
class KnowledgeListCells
{
    public const int MAX_LENGTH = 5000;

    /**
     * The texts keyed by the id of their column, a blank one as null.
     *
     * @param  array<string, string|null>  $cells
     * @return array<int, string|null>
     */
    public static function from(KnowledgeList $list, array $cells): array
    {
        $columns = $list->columns()->get()->keyBy('uuid');
        $values = [];

        foreach ($cells as $uuid => $value) {
            $column = $columns->get($uuid);

            if (! $column instanceof KnowledgeListColumn) {
                throw ValidationException::withMessages(["cells.{$uuid}" => __('No column of this list has this identifier.')]);
            }

            if (mb_strlen((string) $value) > self::MAX_LENGTH) {
                throw ValidationException::withMessages(["cells.{$uuid}" => __('Keep each cell under 5,000 characters.')]);
            }

            $values[$column->id] = blank($value) ? null : mb_trim((string) $value);
        }

        return $values;
    }

    /** @param  array<int, string|null>  $values */
    public static function write(KnowledgeListItem $item, array $values): void
    {
        foreach ($values as $columnId => $value) {
            if ($value === null) {
                KnowledgeListCell::query()
                    ->where('knowledge_list_item_id', $item->id)
                    ->where('knowledge_list_column_id', $columnId)
                    ->delete();

                continue;
            }

            KnowledgeListCell::query()->updateOrCreate(
                ['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $columnId],
                ['account_id' => $item->account_id, 'vault_id' => $item->vault_id, 'value' => $value],
            );
        }
    }

    /** @param  array<int, string|null>  $values */
    public static function holdsNothing(array $values): bool
    {
        return array_filter($values, fn (?string $value): bool => $value !== null) === [];
    }

    public static function refuseBlank(): never
    {
        throw ValidationException::withMessages(['cells' => __('Write something in at least one column.')]);
    }
}
