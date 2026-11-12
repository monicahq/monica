<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckSearchEngine;
use App\Models\Contact;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckSearchEngineTest extends TestCase
{
    use RefreshDatabase;

    private const string KEY = 'threat-level-midnight';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    #[Test]
    public function it_has_no_status_for_an_engine_it_cannot_question_still_counts_the_records_and_writes_nothing_down(): void
    {
        Http::fake();
        Queue::fake();
        config(['scout.driver' => 'collection', 'scout.prefix' => 'scranton_']);
        Contact::factory()->count(2)->create();

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('collection', $status['driver']);
        $this->assertSame('unavailable', $status['health']);
        $this->assertSame('scranton_', $status['prefix']);
        $this->assertFalse($status['isQueued']);
        $this->assertSame(['contacts', 'entities', 'life-events'], array_column($status['indexes'], 'kind'));
        $this->assertSame('scranton_contacts', $status['indexes'][0]['name']);
        $this->assertSame(2, $status['indexes'][0]['recordCount']);
        $this->assertNull($status['indexes'][0]['documentCount']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_counts_what_the_database_engine_has_written_without_calling_anything(): void
    {
        Http::fake();
        config(['scout.driver' => 'database']);
        $contacts = Contact::factory()->count(3)->create();
        Contact::query()->toBase()->update(['search_name' => 'Jim Halpert']);
        Contact::query()->whereKey($contacts->first()->id)->toBase()->update(['search_name' => null]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('healthy', $status['health']);
        $this->assertNull($status['responseTimeInMilliseconds']);
        $this->assertSame(3, $status['indexes'][0]['recordCount']);
        $this->assertSame(2, $status['indexes'][0]['documentCount']);
        $this->assertTrue($status['indexes'][0]['exists']);
        $this->assertNull($status['indexes'][0]['sizeInBytes']);
        $this->assertNull($status['totalSizeInBytes']);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_reads_meilisearch(): void
    {
        $this->useEngine('meilisearch');
        Contact::factory()->count(2)->create();
        Http::fake([
            'search.dundermifflin.com:7700/health' => Http::response(['status' => 'available']),
            'search.dundermifflin.com:7700/version' => Http::response(['pkgVersion' => '1.17.0']),
            'search.dundermifflin.com:7700/stats' => Http::response(['databaseSize' => 58126336, 'indexes' => [
                'contacts' => ['numberOfDocuments' => 1, 'rawDocumentDbSize' => 4096, 'isIndexing' => true],
                'entities' => ['numberOfDocuments' => 0, 'rawDocumentDbSize' => 0, 'isIndexing' => false],
            ]]),
        ]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('healthy', $status['health']);
        $this->assertSame('search.dundermifflin.com', $status['address']);
        $this->assertSame('1.17.0', $status['version']);
        $this->assertIsInt($status['responseTimeInMilliseconds']);
        $this->assertTrue($status['isIndexing']);
        $this->assertSame(58126336, $status['totalSizeInBytes']);
        $this->assertSame(2, $status['indexes'][0]['recordCount']);
        $this->assertSame(1, $status['indexes'][0]['documentCount']);
        $this->assertSame(4096, $status['indexes'][0]['sizeInBytes']);
        $this->assertTrue($status['indexes'][1]['exists']);
        $this->assertFalse($status['indexes'][2]['exists']);
        $this->assertNull($status['indexes'][2]['documentCount']);
        $this->assertStringNotContainsString(self::KEY, (string) json_encode($status));
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer '.self::KEY));
    }

    #[Test]
    public function it_reads_typesense_which_reports_no_size(): void
    {
        $this->useEngine('typesense');
        Http::fake([
            'search.dundermifflin.com:8108/health' => Http::response(['ok' => true]),
            'search.dundermifflin.com:8108/debug' => Http::response(['state' => 1, 'version' => '29.0']),
            'search.dundermifflin.com:8108/collections' => Http::response([
                ['name' => 'contacts', 'num_documents' => 12],
                ['name' => 'entities', 'num_documents' => 3],
                ['name' => 'life_events', 'num_documents' => 40],
            ]),
        ]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('healthy', $status['health']);
        $this->assertSame('search.dundermifflin.com', $status['address']);
        $this->assertSame('29.0', $status['version']);
        $this->assertNull($status['isIndexing']);
        $this->assertNull($status['totalSizeInBytes']);
        $this->assertSame([12, 3, 40], array_column($status['indexes'], 'documentCount'));
        $this->assertNull($status['indexes'][0]['sizeInBytes']);
        $this->assertStringNotContainsString(self::KEY, (string) json_encode($status));
        Http::assertSent(fn ($request): bool => $request->hasHeader('X-TYPESENSE-API-KEY', self::KEY));
    }

    #[Test]
    public function it_reads_algolia_from_its_list_of_indexes(): void
    {
        $this->useEngine('algolia');
        Http::fake([
            'scranton.algolia.net/1/indexes' => Http::response(['items' => [
                ['name' => 'contacts', 'entries' => 12, 'dataSize' => 2048, 'pendingTask' => false],
                ['name' => 'life_events', 'entries' => 40, 'dataSize' => 8192, 'pendingTask' => true],
                ['name' => 'scranton_branch_contacts', 'entries' => 5, 'dataSize' => 1024, 'pendingTask' => false],
            ]]),
        ]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('healthy', $status['health']);
        $this->assertSame('scranton.algolia.net', $status['address']);
        $this->assertNull($status['version']);
        $this->assertTrue($status['isIndexing']);
        $this->assertSame(12, $status['indexes'][0]['documentCount']);
        $this->assertSame(2048, $status['indexes'][0]['sizeInBytes']);
        $this->assertSame(11264, $status['totalSizeInBytes']);
        $this->assertFalse($status['indexes'][1]['exists']);
        $this->assertSame(40, $status['indexes'][2]['documentCount']);
        $this->assertStringNotContainsString(self::KEY, (string) json_encode($status));
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_says_the_engine_is_unreachable_and_hides_the_key_in_the_reason(): void
    {
        $this->useEngine('meilisearch');
        Http::fake(fn () => throw new ConnectionException('Could not connect with key '.self::KEY));

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('unreachable', $status['health']);
        $this->assertSame('Could not connect with key ***', $status['reason']);
        $this->assertNull($status['indexes'][0]['documentCount']);
    }

    #[Test]
    public function it_says_the_engine_is_unreachable_when_it_refuses_the_credentials(): void
    {
        $this->useEngine('typesense');
        Http::fake(['search.dundermifflin.com:8108/health' => Http::response(['message' => 'Forbidden'], 401)]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('unreachable', $status['health']);
        $this->assertSame('HTTP 401', $status['reason']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_stays_healthy_and_says_so_on_each_index_when_only_the_indexes_fail(): void
    {
        $this->useEngine('meilisearch');
        Http::fake([
            'search.dundermifflin.com:7700/health' => Http::response(['status' => 'available']),
            'search.dundermifflin.com:7700/version' => Http::response(['pkgVersion' => '1.17.0']),
            'search.dundermifflin.com:7700/stats' => Http::response([], 500),
        ]);

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('healthy', $status['health']);
        $this->assertSame('1.17.0', $status['version']);
        $this->assertNull($status['indexes'][0]['documentCount']);
        $this->assertNull($status['indexes'][0]['exists']);
    }

    #[Test]
    public function it_says_the_engine_is_unreachable_when_its_client_is_not_installed(): void
    {
        Http::fake();
        config(['scout.driver' => 'algolia']);
        $this->mock(EngineManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('engine')->andThrow(new Exception('Please install the suggested Algolia client: algolia/algoliasearch-client-php.'));
        });

        $status = new CheckSearchEngine()->execute();

        $this->assertSame('unreachable', $status['health']);
        $this->assertSame('Please install the suggested Algolia client: algolia/algoliasearch-client-php.', $status['reason']);
        Http::assertNothingSent();
    }

    private function useEngine(string $driver): void
    {
        $this->mock(EngineManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('engine')->andReturn(new NullEngine);
        });

        config([
            'scout.driver' => $driver,
            'scout.meilisearch.host' => 'https://search.dundermifflin.com:7700/',
            'scout.meilisearch.key' => self::KEY,
            'scout.typesense.client-settings.api_key' => self::KEY,
            'scout.typesense.client-settings.nodes' => [['host' => 'search.dundermifflin.com', 'port' => '8108', 'path' => '', 'protocol' => 'https']],
            'scout.algolia.id' => 'scranton',
            'scout.algolia.secret' => self::KEY,
        ]);
    }
}
