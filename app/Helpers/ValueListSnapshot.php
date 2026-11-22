<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\SectionVersion;
use App\Models\ValueList;
use App\Models\ValueListItem;

class ValueListSnapshot
{
    /** @return array<int, array<string, mixed>> */
    public static function of(SectionVersion $sectionVersion): array
    {
        $ids = $sectionVersion->fields()->whereNotNull('value_list_id')->pluck('value_list_id')->unique();

        $lists = ValueList::query()->whereIn('id', $ids)->get();

        $snapshot = [];

        foreach ($lists as $list) {
            $snapshot[$list->id] = [
                'id' => $list->id,
                'key' => $list->key,
                'name' => $list->getRawOriginal('name'),
                'name_translation_key' => $list->name_translation_key,
                'items' => $list->items()->orderBy('position')->get()->map(fn (ValueListItem $item): array => [
                    'id' => $item->id,
                    'key' => $item->key,
                    'label' => $item->getRawOriginal('label'),
                    'label_translation_key' => $item->label_translation_key,
                    'country_code' => $item->country_code,
                    'position' => $item->position,
                    'is_active' => $item->is_active,
                ])->all(),
            ];
        }

        return $snapshot;
    }
}
