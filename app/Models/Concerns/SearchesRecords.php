<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\FieldTypeEnum;
use App\Helpers\FieldValueFormatter;
use App\Helpers\MaterializedDatabaseEngine;
use App\Helpers\SearchWords;
use App\Models\Account;
use App\Models\FieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Engines\DatabaseEngine;
use Laravel\Scout\Searchable;

/**
 * What a contact, an entity and a life event share to be found by search: the document the search
 * engine is sent, and, for a result, which part of the record the words were found in.
 *
 * The document holds the values as the owner reads them, in the language of the account, whatever
 * language the queued job that builds it runs in, because that is what somebody types.
 *
 * @property int $account_id
 * @property int $vault_id
 * @property string|null $search_name
 * @property string|null $search_body
 * @property string|null $search_sensitive_body
 * @property string|null $search_linked_names
 * @property-read Account $account
 * @property-read Collection<int, FieldValue> $fieldValues
 */
trait SearchesRecords
{
    use Searchable;

    /** The name the record is listed under. */
    abstract public function searchableName(): string;

    /**
     * The people or records named on this one, each with the line a result shows when the words
     * were found in that name.
     *
     * @return list<array{name: string, label: string}>
     */
    abstract public function searchableLinkedNames(): array;

    /**
     * Text the record carries itself, outside its fields.
     *
     * @return list<string>
     */
    public function searchableOwnText(): array
    {
        return [];
    }

    /** The entity type an entity is grouped under, null for any other record. */
    public function searchableEntityTypeId(): ?int
    {
        return null;
    }

    /**
     * The relations the document reads, beyond the values, loaded at once for a batch.
     *
     * @return list<string>
     */
    public function searchableRelations(): array
    {
        return [];
    }

    /**
     * Send the search engine again every record the query reaches, a chunk at a time, queued
     * like any other sync.
     *
     * @param  Builder<static>  $query
     */
    public static function sendToSearch(Builder $query): void
    {
        $query->chunkById((int) config('scout.chunk.searchable', 500), function (Collection $models): void {
            $models->first()?->queueMakeSearchable($models);
        });
    }

    /**
     * Take every record the query reaches out of the search engine, a chunk at a time.
     *
     * @param  Builder<static>  $query
     */
    public static function withdrawFromSearch(Builder $query): void
    {
        $query->chunkById((int) config('scout.chunk.unsearchable', 500), function (Collection $models): void {
            $models->first()?->queueRemoveFromSearch($models);
        });
    }

    /**
     * What the search engine reads. An outside engine is sent the document. The database engine
     * searches the columns the document is written to, and reads only their names from here, on a
     * record it has not loaded.
     *
     * @return array<string, int|string|null>
     */
    #[SearchUsingFullText(MaterializedDatabaseEngine::COLUMNS, ['language' => 'simple'])]
    public function toSearchableArray(): array
    {
        if ($this->searchableUsing() instanceof DatabaseEngine) {
            return $this->only(MaterializedDatabaseEngine::COLUMNS);
        }

        return $this->searchableDocument();
    }

    /**
     * The record as somebody searches it: the name, the values that are not sensitive, the
     * sensitive ones apart, and the names linked to it.
     *
     * @return array{id: string, account_id: int, vault_id: int, entity_type_id: ?int, name: string, body: string, sensitive_body: string, linked_names: string}
     */
    public function searchableDocument(): array
    {
        $values = collect($this->searchableValues());

        return [
            'id' => (string) $this->getScoutKey(),
            'account_id' => (int) $this->account_id,
            'vault_id' => (int) $this->vault_id,
            'entity_type_id' => $this->searchableEntityTypeId(),
            'name' => $this->searchableName(),
            'body' => $values->where('sensitive', false)->pluck('text')->merge($this->searchableOwnText())->implode("\n"),
            'sensitive_body' => $values->where('sensitive', true)->pluck('text')->implode("\n"),
            'linked_names' => collect($this->searchableLinkedNames())->pluck('name')->implode("\n"),
        ];
    }

    /**
     * @param  Collection<int, static>  $models
     * @return Collection<int, static>
     */
    public function makeSearchableUsing(Collection $models): Collection
    {
        return $models->load([
            'account.userIncludingDeleted',
            'fieldValues.field',
            'fieldValues.valueListItem',
            'fieldValues.file',
            'fieldValues.relatedContact',
            'fieldValues.relatedEntity',
            ...$this->searchableRelations(),
        ]);
    }

    /**
     * Every attached value that says something, as the owner reads it. A yes or no says nothing
     * without its question, and an avatar is a picture, so neither is searched.
     *
     * @return list<array{label: string, text: string, sensitive: bool}>
     */
    public function searchableValues(): array
    {
        $user = $this->account->userIncludingDeleted;

        if ($user === null) {
            return [];
        }

        $previousLocale = App::getLocale();
        App::setLocale($this->account->locale());

        try {
            $formatter = new FieldValueFormatter($user);
            $values = [];

            foreach ($this->fieldValues as $value) {
                if ($value->is_detached || ! $value->field->type->storesValue()) {
                    continue;
                }

                if (in_array($value->field->type, [FieldTypeEnum::Boolean, FieldTypeEnum::Avatar], true)) {
                    continue;
                }

                $text = $formatter->formatForSearch($value);

                if ($text === null || mb_trim($text) === '') {
                    continue;
                }

                $values[] = ['label' => $value->field->label, 'text' => $text, 'sensitive' => $value->field->is_sensitive];
            }

            return $values;
        } finally {
            App::setLocale($previousLocale);
        }
    }

    /**
     * Where a result's words were found, for the line under its name: nothing when the name holds
     * one, else the first value, then the first linked name, holding one. A word counts as found
     * exactly, as the start of a longer word, or within the typing mistakes the engines tolerate.
     * A sensitive value gives its label and never its text.
     *
     * @param  list<string>  $words
     * @return array{label: string, excerpt: ?string}|null
     */
    public function matchedField(array $words, bool $withSensitive): ?array
    {
        $locale = $this->account->locale();

        if (SearchWords::holdsAny($this->searchableName(), $words, $locale)) {
            return null;
        }

        foreach ($this->searchableValues() as $value) {
            if ($value['sensitive'] && ! $withSensitive) {
                continue;
            }

            if (SearchWords::holdsAny($value['text'], $words, $locale)) {
                return ['label' => $value['label'], 'excerpt' => $value['sensitive'] ? null : $value['text']];
            }
        }

        foreach ($this->searchableLinkedNames() as $linked) {
            if (SearchWords::holdsAny($linked['name'], $words, $locale)) {
                return ['label' => $linked['label'], 'excerpt' => null];
            }
        }

        return null;
    }
}
