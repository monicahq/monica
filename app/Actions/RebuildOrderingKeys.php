<?php

declare(strict_types=1);

namespace App\Actions;

use App\Casts\CollationKey;
use App\Helpers\LocaleCollation;
use App\Helpers\NameFiling;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Write every ordering key in an account again, for a language, the keys being derived from the
 * language the account reads in. A contact's sort name is rebuilt with them, since the language
 * also decides which part of a name it is filed under.
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
        $this->rebuildContacts();
        $this->rebuildEntities();
    }

    private function rebuildContacts(): void
    {
        Contact::query()
            ->withTrashed()
            ->where('account_id', $this->account->id)
            ->select(['id', 'last_name', 'first_name', 'middle_name', 'formatted_name', 'nickname', 'maiden_name'])
            ->when(NameFiling::phoneticScripts($this->locale) !== [], fn (Builder $query): Builder => $query->with('nameRepresentations'))
            ->chunkById(self::CHUNK, function (Collection $contacts): void {
                foreach ($contacts->chunk(self::WRITE_BATCH) as $batch) {
                    $this->write('contacts', $batch->mapWithKeys(fn (Contact $contact): array => [$contact->id => [
                        'sort_name' => $contact->sortNameFor($this->locale),
                        'search_key' => $contact->searchKeyFor($this->locale),
                    ]])->all(), writesSortName: true);
                }
            });
    }

    private function rebuildEntities(): void
    {
        Entity::query()
            ->withTrashed()
            ->where('account_id', $this->account->id)
            ->select(['id', 'sort_name'])
            ->chunkById(self::CHUNK, function (Collection $entities): void {
                foreach ($entities->chunk(self::WRITE_BATCH) as $batch) {
                    $this->write('entities', $batch->mapWithKeys(fn (Entity $entity): array => [$entity->id => [
                        'sort_name' => $entity->sort_name,
                        'search_key' => LocaleCollation::foldForSearch($entity->sort_name, $this->locale),
                    ]])->all(), writesSortName: false);
                }
            });
    }

    /** @param  array<int, array{sort_name: string, search_key: string}>  $rows */
    private function write(string $table, array $rows, bool $writesSortName): void
    {
        if ($rows === []) {
            return;
        }

        $sortNameWhens = '';
        $sortWhens = '';
        $searchWhens = '';
        $sortNameBindings = [];
        $sortBindings = [];
        $searchBindings = [];

        // PostgreSQL types a bare parameter inside a case as text, which a bytea column refuses.
        $sortValue = DB::connection()->getDriverName() === 'pgsql' ? 'cast(? as bytea)' : '?';

        foreach ($rows as $id => $row) {
            $sortNameWhens .= ' when ? then ?';
            $sortNameBindings[] = $id;
            $sortNameBindings[] = $row['sort_name'];

            $sortWhens .= " when ? then {$sortValue}";
            $sortBindings[] = $id;
            $sortBindings[] = CollationKey::bindable(
                LocaleCollation::sortKey($row['sort_name'], $this->locale),
                DB::connection(),
            );

            $searchWhens .= ' when ? then ?';
            $searchBindings[] = $id;
            $searchBindings[] = $row['search_key'];
        }

        $sortNameSet = $writesSortName ? "sort_name = case id{$sortNameWhens} end, " : '';

        DB::update(
            "update {$table} set {$sortNameSet}sort_key = case id{$sortWhens} end, search_key = case id{$searchWhens} end"
                .' where account_id = ? and id between ? and ?',
            [
                ...($writesSortName ? $sortNameBindings : []),
                ...$sortBindings,
                ...$searchBindings,
                $this->account->id,
                array_key_first($rows),
                array_key_last($rows),
            ],
        );
    }
}
