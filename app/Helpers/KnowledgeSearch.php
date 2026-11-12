<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use Illuminate\Support\Collection;

/**
 * Which notes and lists of a vault's Knowledge hold every word somebody typed, in their title,
 * their text, their description or any cell. Words are read the way the vault search reads them:
 * case and accents ignored as the account's language ignores them, a word found at the start of a
 * longer one, and a typing mistake tolerated. The screen and the API both ask it.
 */
class KnowledgeSearch
{
    /**
     * @return array{notes: Collection<int, KnowledgeNote>, lists: Collection<int, KnowledgeList>}
     */
    public static function in(Vault $vault, string $query): array
    {
        $words = SearchWords::split($query);

        if ($words === []) {
            return ['notes' => collect(), 'lists' => collect()];
        }

        $locale = $vault->account->locale();

        $notes = KnowledgeNote::query()
            ->where('vault_id', $vault->id)
            ->with('folder.vault')
            ->get()
            ->filter(fn (KnowledgeNote $note): bool => self::holdsEvery([$note->title, (string) $note->body], $words, $locale))
            ->values();

        $lists = KnowledgeList::query()
            ->where('vault_id', $vault->id)
            ->with('folder.vault')
            ->get();

        $cells = KnowledgeListCell::query()
            ->join('knowledge_list_items', 'knowledge_list_items.id', '=', 'knowledge_list_cells.knowledge_list_item_id')
            ->where('knowledge_list_cells.vault_id', $vault->id)
            ->get(['knowledge_list_items.knowledge_list_id', 'knowledge_list_cells.value'])
            ->groupBy('knowledge_list_id');

        $lists = $lists
            ->filter(fn (KnowledgeList $list): bool => self::holdsEvery([
                $list->title,
                (string) $list->description,
                ...$cells->get($list->id, collect())->pluck('value')->all(),
            ], $words, $locale))
            ->values();

        return ['notes' => $notes, 'lists' => $lists];
    }

    /**
     * Each text is split into its distinct words once, since a long note repeats most of its words
     * and every typed word is compared with all of them.
     *
     * @param  list<string>  $texts
     * @param  list<string>  $words
     */
    private static function holdsEvery(array $texts, array $words, string $locale): bool
    {
        $written = [];

        foreach ($texts as $text) {
            foreach (SearchWords::split($text) as $word) {
                $written[$word] = true;
            }
        }

        foreach ($words as $typed) {
            $found = false;

            foreach (array_keys($written) as $word) {
                if (SearchWords::matches((string) $word, $typed, $locale)) {
                    $found = true;

                    break;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }
}
