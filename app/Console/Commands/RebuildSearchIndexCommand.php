<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\MaterializedDatabaseEngine;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use App\Models\Vault;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Contracts\UpdatesIndexSettings;
use Laravel\Scout\EngineManager;

#[Signature('monica:rebuild-search-index {--account= : Id of one account, every account when left out} {--from= : The engine searched until now, whose index is emptied, when SCOUT_DRIVER was just changed}')]
#[Description('Empty the search engine, then send every contact, entity and life event to it again, vault by vault')]
class RebuildSearchIndexCommand extends Command
{
    private const array SUPPORTED_ENGINES = ['algolia', 'meilisearch', 'typesense', 'database'];

    private const array SEARCHABLE_MODELS = [Contact::class, Entity::class, LifeEvent::class];

    public function handle(EngineManager $engines): int
    {
        $driver = config('scout.driver');

        if (! in_array($driver, self::SUPPORTED_ENGINES, true)) {
            $this->error('Search needs an engine: set SCOUT_DRIVER to algolia, meilisearch, typesense or database.');

            return self::FAILURE;
        }

        $id = $this->option('account');

        if ($id !== null && ! Account::query()->whereKey($id)->exists()) {
            $this->error("No account has the id {$id}.");

            return self::FAILURE;
        }

        $previous = $this->option('from');

        if ($previous !== null && ! in_array($previous, self::SUPPORTED_ENGINES, true)) {
            $this->error("{$previous} is not an engine search runs on: name algolia, meilisearch, typesense or database.");

            return self::FAILURE;
        }

        if ($previous === $driver) {
            $this->error("{$previous} is the engine SCOUT_DRIVER names already: --from names the one searched until now.");

            return self::FAILURE;
        }

        if ($previous !== null && $id !== null) {
            $this->error('The engine searched until now is emptied for every account at once, so --from cannot be given with --account.');

            return self::FAILURE;
        }

        $tablesWithoutIndex = $this->tablesWithoutTheirFullTextIndex();

        if ($tablesWithoutIndex !== []) {
            $this->error('The database engine searches through a full text index that '.implode(', ', $tablesWithoutIndex).' does not have: set FULL_TEXT_INDEX=true and migrate the database again, or choose another engine.');

            return self::FAILURE;
        }

        if ($engines->engine() instanceof UpdatesIndexSettings) {
            $this->call('scout:sync-index-settings');
        }

        if ($driver !== 'database') {
            foreach (self::SEARCHABLE_MODELS as $model) {
                $model::withTrashed()
                    ->when($id !== null, fn (Builder $query): Builder => $query->where('account_id', $id))
                    ->toBase()
                    ->update(array_fill_keys(MaterializedDatabaseEngine::COLUMNS, null));
            }

            $this->line('Emptied the search columns the database engine reads.');
        }

        if ($id === null) {
            foreach (self::SEARCHABLE_MODELS as $model) {
                $engines->engine()->flush(new $model);
            }

            $this->line("Emptied the {$driver} index.");
        }

        if ($previous !== null && $previous !== 'database') {
            foreach (self::SEARCHABLE_MODELS as $model) {
                $engines->engine($previous)->flush(new $model);
            }

            $this->line("Emptied the {$previous} index.");
        }

        $records = 0;
        $vaults = 0;

        Vault::query()
            ->when($id !== null, fn (Builder $query): Builder => $query->where('account_id', $id))
            ->orderBy('id')
            ->each(function (Vault $vault) use (&$records, &$vaults): void {
                $contacts = Contact::query()->where('vault_id', $vault->id);
                $entities = Entity::query()->where('vault_id', $vault->id);
                $lifeEvents = LifeEvent::query()->where('vault_id', $vault->id);
                $count = $contacts->count() + $entities->count() + $lifeEvents->count();

                $this->line("Vault {$vault->id}: {$count} records");

                Contact::sendToSearch($contacts);
                Entity::sendToSearch($entities);
                LifeEvent::sendToSearch($lifeEvents);

                $records += $count;
                $vaults++;
            });

        if (config('scout.queue')) {
            $this->info("Queued {$records} records in {$vaults} vaults: they reach the index as a queue worker runs them.");

            return self::SUCCESS;
        }

        $this->info("Indexed {$records} records in {$vaults} vaults.");

        return self::SUCCESS;
    }

    /**
     * The tables the database engine would search without the full text index it needs, which the
     * migrations leave out when FULL_TEXT_INDEX is false. SQLite has no full text index and is
     * searched with LIKE, so nothing is missing there.
     *
     * @return list<string>
     */
    private function tablesWithoutTheirFullTextIndex(): array
    {
        if (config('scout.driver') !== 'database') {
            return [];
        }

        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            return [];
        }

        return array_values(array_filter(
            ['contacts', 'entities', 'life_events'],
            fn (string $table): bool => ! in_array("{$table}_search_fulltext", array_column(Schema::getIndexes($table), 'name'), true),
        ));
    }
}
