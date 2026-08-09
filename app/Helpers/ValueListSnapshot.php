<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\SectionVersion;
use App\Models\ValueList;
use App\Models\ValueListItem;

/**
 * Takes the copy of the value lists a section version is frozen with.
 *
 * Without it the freeze would stop at the edge of the fields table: somebody
 * renaming an option on the settings screen would change what a section
 * published last month says, and that is exactly what publication rules out.
 *
 * The copy is not what gets read day to day. A stored value still resolves
 * through the live item while it is still there, so an option somebody
 * relabelled reads with its new label. The copy is what answers when the item
 * has gone, and it is what travels when the section is installed somewhere else.
 *
 * The labels are taken as they sit in the database, translation key and all,
 * rather than translated on the way in. Translating here would bake the
 * language of whoever pressed the button into the snapshot.
 */
class ValueListSnapshot
{
    /**
     * Copy the lists the fields of this version actually point at. The rest of
     * the account's lists have nothing to do with this section.
     *
     * @return array<int, array<string, mixed>>
     */
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
                    'position' => $item->position,
                    'is_active' => $item->is_active,
                ])->all(),
            ];
        }

        return $snapshot;
    }
}
