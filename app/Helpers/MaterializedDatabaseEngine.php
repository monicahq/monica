<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\DatabaseEngine;
use Override;

/**
 * Scout's database engine, made to search what a record says rather than its raw columns.
 *
 * What a record says is computed: values formatted as the owner reads them, the labels of the
 * lists they come from, the names linked to it. A full-text index needs real columns, so the
 * document an outside engine would be sent is written to the record's `search_*` columns instead,
 * and searched there. Every word must be found, in any order, as a word or as the start of one.
 *
 * MySQL, MariaDB and PostgreSQL search the columns through their full-text index. SQLite has none,
 * and falls back to LIKE, which is slower and does not rank by relevance.
 */
class MaterializedDatabaseEngine extends DatabaseEngine
{
    /** The columns the document is written to, and searched in. */
    public const array COLUMNS = ['search_name', 'search_body', 'search_sensitive_body', 'search_linked_names'];

    /** InnoDB leaves a word shorter than this out of its full-text index, so a full-text search never finds it. */
    private const int SHORTEST_INDEXED_WORD = 3;

    /** The words of three letters or more InnoDB leaves out of its full-text index by default, so a full-text search never finds them either. */
    private const array INNODB_STOPWORDS = ['about', 'are', 'com', 'for', 'from', 'how', 'that', 'the', 'this', 'und', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'www'];

    /** What may come before a word in a column: nothing, a space, a new line between two values, a hyphen. */
    private const array WORD_STARTS = ['', '% ', "%\n", '%-'];

    /** @param Collection<int, Contact|Entity|LifeEvent> $models */
    #[Override]
    public function update($models): void
    {
        parent::update($models);

        foreach ($models as $model) {
            $document = $model->searchableDocument();

            $model->newQueryWithoutScopes()->whereKey($model->getKey())->toBase()->update([
                'search_name' => $document['name'],
                'search_body' => $document['body'],
                'search_sensitive_body' => $document['sensitive_body'],
                'search_linked_names' => $document['linked_names'],
            ]);
        }
    }

    /** @param Collection<int, Contact|Entity|LifeEvent> $models */
    #[Override]
    public function delete($models): void
    {
        $model = $models->first();

        if ($model === null) {
            return;
        }

        $model->newQueryWithoutScopes()->whereKey($models->modelKeys())->toBase()->update(array_fill_keys(self::COLUMNS, null));
    }

    /** @param Contact|Entity|LifeEvent $model */
    #[Override]
    public function flush($model): void
    {
        $model->newQueryWithoutScopes()->toBase()->update(array_fill_keys(self::COLUMNS, null));
    }

    /**
     * Every word must be found in one of the columns, and a record whose name holds every word
     * comes first. A caller may narrow the columns with the `columns` option, which is how a
     * sensitive value is kept out of reach of a caller who may not read it.
     *
     * @param  EloquentBuilder<Contact|Entity|LifeEvent>  $query
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $prefixColumns
     * @param  array<int, string>  $fullTextColumns
     * @return EloquentBuilder<Contact|Entity|LifeEvent>
     */
    #[Override]
    protected function addTextSearchConstraints($query, Builder $builder, array $columns, array $prefixColumns = [], array $fullTextColumns = [])
    {
        $words = SearchWords::split((string) $builder->query);

        if ($words === []) {
            return $query;
        }

        $model = $builder->model;
        $connection = $builder->modelConnectionType();
        $like = $connection === 'pgsql' ? 'ilike' : 'like';
        $options = $this->getFullTextOptions($builder);

        /** @var list<string> $searched */
        $searched = array_map(
            fn (string $column): string => $model->qualifyColumn($column),
            $builder->options['columns'] ?? $fullTextColumns,
        );

        if ($connection === 'pgsql') {
            $query->whereFullText($searched, implode(' & ', array_map(fn (string $word): string => "{$word}:*", $words)), [...$options, 'mode' => 'raw']);
        } elseif (in_array($connection, ['mysql', 'mariadb'], true)) {
            $indexed = array_values(array_filter(
                $words,
                fn (string $word): bool => mb_strlen($word) >= self::SHORTEST_INDEXED_WORD && ! in_array(mb_strtolower($word), self::INNODB_STOPWORDS, true),
            ));

            if ($indexed !== []) {
                $query->whereFullText($searched, '+'.implode('* +', $indexed).'*', [...$options, 'mode' => 'boolean']);
            }

            $this->whereEveryWordIsLike($query, $searched, array_diff($words, $indexed), $like);
        } else {
            $this->whereEveryWordIsLike($query, $searched, $words, $like);
        }

        $name = $model->qualifyColumn('search_name');
        $nameHoldsAWord = '('.implode(' or ', array_fill(0, count(self::WORD_STARTS), "{$name} {$like} ?")).')';

        $query->orderByRaw(
            'case when '.implode(' and ', array_fill(0, count($words), $nameHoldsAWord)).' then 0 else 1 end',
            array_merge(...array_map($this->wordStartPatterns(...), $words)),
        );

        if ($connection !== 'pgsql') {
            $query->orderByDesc($model->getQualifiedKeyName());
        }

        return $query;
    }

    /**
     * @param  EloquentBuilder<Contact|Entity|LifeEvent>  $query
     * @param  list<string>  $columns
     * @param  array<int, string>  $words
     */
    private function whereEveryWordIsLike(EloquentBuilder $query, array $columns, array $words, string $like): void
    {
        foreach ($words as $word) {
            $query->where(function (EloquentBuilder $query) use ($columns, $word, $like): void {
                foreach ($columns as $column) {
                    foreach ($this->wordStartPatterns($word) as $pattern) {
                        $query->orWhere($column, $like, $pattern);
                    }
                }
            });
        }
    }

    /**
     * The LIKE patterns finding a word, or the start of one, the way a full-text index does,
     * rather than anywhere inside a longer word.
     *
     * @return list<string>
     */
    private function wordStartPatterns(string $word): array
    {
        return array_map(fn (string $start): string => "{$start}{$word}%", self::WORD_STARTS);
    }
}
