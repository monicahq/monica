<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Write every ordering key in an account again, for a language, the keys being
 * derived from the language the account reads in.
 *
 * Opens no transaction of its own: the caller wraps this and the language change
 * together, because a list ordered on keys half in one language and half in
 * another can show a row twice or miss it, and nobody can see that happen.
 *
 * Written through the query builder so the saving hooks do not fire. They read
 * the language from the account, which is the thing being changed.
 */
class RebuildOrderingKeys
{
    /** Large: reading is one statement whatever it brings back. */
    private const int CHUNK = 500;

    /**
     * Much smaller, and not the same question. A `case` is evaluated for every
     * row a statement touches, so work inside a batch grows with the square of
     * its size while the round trips it saves fall only linearly. Measured over
     * 5000 rows on SQLite: 35 ms one row at a time, 12 ms at 25, 46 ms at 200.
     */
    private const int WRITE_BATCH = 25;

    public function __construct(
        private readonly Account $account,
        private readonly string $locale,
    ) {}

    public function execute(): void
    {
        $this->rebuild(Contact::query()->withTrashed(), 'contacts');
        $this->rebuild(Entity::query()->withTrashed(), 'entities');
    }

    /**
     * Deleted records are rebuilt too: one restored carrying a stale key is filed
     * where its reader will not look.
     *
     * @template TRecord of Contact|Entity
     *
     * @param  Builder<TRecord>  $query
     */
    private function rebuild(Builder $query, string $table): void
    {
        $query->where('account_id', $this->account->id)
            ->select(['id', 'sort_name'])
            ->chunkById(self::CHUNK, function (Collection $records) use ($table): void {
                foreach ($records->chunk(self::WRITE_BATCH) as $batch) {
                    $this->write($table, $batch);
                }
            });
    }

    /**
     * One statement a batch rather than one a row, a round trip each being most
     * of the time spent against a database reached over a socket. Computing the
     * keys is not what costs: five thousand take about 35 ms.
     *
     * The rows are named as the account's between the first and last id of the
     * batch rather than listed, `chunkById` reading them in that order.
     *
     * @param  Collection<int, Contact>|Collection<int, Entity>  $records
     */
    private function write(string $table, Collection $records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        $sortWhens = '';
        $searchWhens = '';
        $sortBindings = [];
        $searchBindings = [];

        foreach ($records as $record) {
            $sortWhens .= ' when ? then ?';
            $sortBindings[] = $record->id;
            $sortBindings[] = LocaleCollation::sortKey($record->sort_name, $this->locale);

            $searchWhens .= ' when ? then ?';
            $searchBindings[] = $record->id;
            $searchBindings[] = LocaleCollation::foldForSearch($record->sort_name, $this->locale);
        }

        DB::update(
            "update {$table} set sort_key = case id{$sortWhens} end, search_key = case id{$searchWhens} end"
                .' where account_id = ? and id between ? and ?',
            [
                ...$sortBindings,
                ...$searchBindings,
                $this->account->id,
                $records->first()->id,
                $records->last()->id,
            ],
        );
    }
}
