<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Scout\RecordingEngine;
use Tests\TestCase;

class RebuildSearchIndexCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_every_record_again_vault_by_vault(): void
    {
        $engine = $this->useRecordingSearchEngine();
        config(['scout.driver' => 'meilisearch']);
        resolve(EngineManager::class)->extend('meilisearch', fn () => $engine);

        $user = User::factory()->create();
        $office = Vault::factory()->create(['account_id' => $user->account_id]);
        $farm = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $office->id]);
        $beets = Entity::factory()->create(['vault_id' => $farm->id]);
        $engine->forget();

        $this->artisan('monica:rebuild-search-index', ['--account' => $user->account_id])
            ->expectsOutput("Vault {$office->id}: 1 records")
            ->expectsOutput("Vault {$farm->id}: 1 records")
            ->expectsOutput('Indexed 2 records in 2 vaults.')
            ->assertSuccessful();

        $this->assertTrue($engine->wasSent($dwight));
        $this->assertTrue($engine->wasSent($beets));
    }

    #[Test]
    public function it_refuses_to_run_without_a_search_engine(): void
    {
        $this->artisan('monica:rebuild-search-index')
            ->expectsOutput('Search needs an engine: set SCOUT_DRIVER to algolia, meilisearch, typesense or database.')
            ->assertFailed();
    }

    #[Test]
    public function it_empties_the_index_before_sending_every_record_again(): void
    {
        $engine = $this->engineNamed('meilisearch');
        $user = User::factory()->create();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $engine->forget();

        $this->artisan('monica:rebuild-search-index')
            ->expectsOutput('Emptied the meilisearch index.')
            ->assertSuccessful();

        $this->assertSame([Contact::class, Entity::class, LifeEvent::class], $engine->flushed);
        $this->assertTrue($engine->wasSent($dwight));
    }

    #[Test]
    public function it_leaves_the_index_alone_when_rebuilding_one_account_since_every_account_shares_it(): void
    {
        $engine = $this->engineNamed('meilisearch');
        $user = User::factory()->create();

        $this->artisan('monica:rebuild-search-index', ['--account' => $user->account_id])
            ->doesntExpectOutput('Emptied the meilisearch index.')
            ->assertSuccessful();

        $this->assertSame([], $engine->flushed);
    }

    #[Test]
    public function it_empties_the_index_of_the_engine_searched_until_now(): void
    {
        $this->engineNamed('meilisearch');
        $previous = new RecordingEngine;
        resolve(EngineManager::class)->extend('typesense', fn (): RecordingEngine => $previous);

        $this->artisan('monica:rebuild-search-index', ['--from' => 'typesense'])
            ->expectsOutput('Emptied the typesense index.')
            ->assertSuccessful();

        $this->assertSame([Contact::class, Entity::class, LifeEvent::class], $previous->flushed);
    }

    #[Test]
    public function it_empties_the_search_columns_the_database_engine_reads_when_another_engine_takes_over(): void
    {
        $this->engineNamed('meilisearch');
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $mose = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $mose->delete();
        DB::table('contacts')->update(['search_name' => 'Schrute', 'search_sensitive_body' => 'brine']);

        $this->artisan('monica:rebuild-search-index', ['--account' => $user->account_id])
            ->expectsOutput('Emptied the search columns the database engine reads.')
            ->assertSuccessful();

        $this->assertDatabaseHas('contacts', ['id' => $dwight->id, 'search_name' => null, 'search_sensitive_body' => null]);
        $this->assertDatabaseHas('contacts', ['id' => $mose->id, 'search_name' => null, 'search_sensitive_body' => null]);
    }

    #[Test]
    public function it_writes_the_search_columns_again_for_the_database_engine(): void
    {
        config(['scout.driver' => 'database']);
        $user = User::factory()->create();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        DB::table('contacts')->update(['search_name' => 'Michael Scott']);

        $this->artisan('monica:rebuild-search-index')
            ->expectsOutput('Emptied the database index.')
            ->doesntExpectOutput('Emptied the search columns the database engine reads.')
            ->assertSuccessful();

        $this->assertDatabaseHas('contacts', ['id' => $dwight->id, 'search_name' => 'Dwight Schrute']);
    }

    #[Test]
    public function it_says_the_records_are_queued_when_a_queue_worker_sends_them(): void
    {
        Queue::fake();
        $this->engineNamed('meilisearch');
        config(['scout.queue' => ['connection' => null, 'queue' => 'default']]);
        $user = User::factory()->create();
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->artisan('monica:rebuild-search-index', ['--account' => $user->account_id])
            ->expectsOutput('Queued 1 records in 1 vaults: they reach the index as a queue worker runs them.')
            ->assertSuccessful();
    }

    /** @param  array<string, string>  $options */
    #[Test]
    #[DataProvider('engineSearchedUntilNowThatCannotBeEmptied')]
    public function it_refuses_an_engine_searched_until_now_it_cannot_empty(array $options, string $refusal): void
    {
        $this->engineNamed('meilisearch');
        $user = User::factory()->create();

        $this->artisan('monica:rebuild-search-index', str_replace(':account', (string) $user->account_id, $options))
            ->expectsOutput($refusal)
            ->assertFailed();
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function engineSearchedUntilNowThatCannotBeEmptied(): array
    {
        return [
            'an engine search does not run on' => [['--from' => 'elasticsearch'], 'elasticsearch is not an engine search runs on: name algolia, meilisearch, typesense or database.'],
            'the engine already named' => [['--from' => 'meilisearch'], 'meilisearch is the engine SCOUT_DRIVER names already: --from names the one searched until now.'],
            'one account only' => [['--from' => 'typesense', '--account' => ':account'], 'The engine searched until now is emptied for every account at once, so --from cannot be given with --account.'],
        ];
    }

    private function engineNamed(string $driver): RecordingEngine
    {
        $engine = $this->useRecordingSearchEngine();
        config(['scout.driver' => $driver]);
        resolve(EngineManager::class)->extend($driver, fn (): RecordingEngine => $engine);

        return $engine;
    }
}
