<?php

declare(strict_types=1);

namespace App\Actions;

use App\Casts\CollationKey;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Write every ordering key in an account again, for a language, the keys being derived from the
 * language the account reads in.
 */
class RebuildOrderingKeys
{
    private const int CHUNK = 500;

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

    /** @param  Collection<int, Contact>|Collection<int, Entity>  $records */
    private function write(string $table, Collection $records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        $sortWhens = '';
        $searchWhens = '';
        $sortBindings = [];
        $searchBindings = [];

        // PostgreSQL types a bare parameter inside a case as text, which a bytea column refuses.
        $sortValue = DB::connection()->getDriverName() === 'pgsql' ? 'cast(? as bytea)' : '?';

        foreach ($records as $record) {
            $sortWhens .= " when ? then {$sortValue}";
            $sortBindings[] = $record->id;
            $sortBindings[] = CollationKey::bindable(
                LocaleCollation::sortKey($record->sort_name, $this->locale),
                DB::connection(),
            );

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
