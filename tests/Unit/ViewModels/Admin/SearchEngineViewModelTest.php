<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\NumberFormatEnum;
use App\Models\User;
use App\ViewModels\Admin\SearchEngineViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchEngineViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_words_a_healthy_engine(): void
    {
        $viewModel = $this->viewModel(['driver' => 'meilisearch', 'health' => 'healthy', 'responseTimeInMilliseconds' => 12, 'isIndexing' => true]);

        $this->assertSame('Meilisearch', $viewModel->engineName());
        $this->assertSame('Healthy', $viewModel->healthLabel());
        $this->assertSame('success', $viewModel->healthTone());
        $this->assertNull($viewModel->healthNote());
        $this->assertSame('12 ms', $viewModel->responseTime());
        $this->assertNull($viewModel->typingMistakesNote());
        $this->assertSame('Yes, the engine is working through what it received', $viewModel->indexingLabel());
        $this->assertTrue($viewModel->canRebuild());
    }

    #[Test]
    public function it_gives_the_reason_of_an_unreachable_engine_and_offers_no_rebuild(): void
    {
        $viewModel = $this->viewModel(['driver' => 'typesense', 'health' => 'unreachable', 'reason' => 'HTTP 401']);

        $this->assertSame('Unreachable', $viewModel->healthLabel());
        $this->assertSame('danger', $viewModel->healthTone());
        $this->assertSame('HTTP 401', $viewModel->healthNote());
        $this->assertFalse($viewModel->canRebuild());
    }

    #[Test]
    public function it_names_an_engine_it_cannot_question_and_offers_no_rebuild(): void
    {
        $viewModel = $this->viewModel(['driver' => 'collection']);

        $this->assertSame('collection', $viewModel->engineName());
        $this->assertSame('No status available', $viewModel->healthLabel());
        $this->assertSame('This screen does not know how to question this engine.', $viewModel->healthNote());
        $this->assertNull($viewModel->responseTime());
        $this->assertNull($viewModel->indexingLabel());
        $this->assertFalse($viewModel->canRebuild());
    }

    #[Test]
    public function it_says_the_database_engine_does_not_tolerate_typing_mistakes(): void
    {
        $viewModel = $this->viewModel(['driver' => 'database', 'health' => 'healthy']);

        $this->assertSame('The database of this instance', $viewModel->engineName());
        $this->assertSame('This engine does not tolerate typing mistakes. Algolia, Meilisearch and Typesense do.', $viewModel->typingMistakesNote());
    }

    #[Test]
    public function it_words_what_each_index_holds(): void
    {
        $indexes = $this->viewModel(['health' => 'healthy', 'indexes' => [
            $this->index(kind: 'contacts', recordCount: 12, documentCount: 9, sizeInBytes: 4096),
            $this->index(kind: 'entities', recordCount: 12, documentCount: 14),
            $this->index(kind: 'life-events', recordCount: 1, documentCount: 0),
        ]])->indexes();

        $this->assertSame([
            'label' => 'Contacts',
            'name' => 'contacts',
            'documents' => '9',
            'records' => 12,
            'difference' => '3 records missing',
            'size' => '4 KB',
            'rebuildUrl' => route('admin.search.rebuild.create', ['kind' => 'contacts']),
        ], $indexes[0]);
        $this->assertSame('Entities', $indexes[1]['label']);
        $this->assertSame('2 documents left over', $indexes[1]['difference']);
        $this->assertSame('Not reported', $indexes[1]['size']);
        $this->assertSame('Life events', $indexes[2]['label']);
        $this->assertSame('1 record missing', $indexes[2]['difference']);
    }

    #[Test]
    public function it_says_an_index_is_complete_missing_or_unanswered(): void
    {
        $indexes = $this->viewModel(['health' => 'healthy', 'indexes' => [
            $this->index(kind: 'contacts', recordCount: 12, documentCount: 12),
            $this->index(kind: 'entities', recordCount: 3, exists: false),
            $this->index(kind: 'life-events', recordCount: 3, exists: null),
        ]])->indexes();

        $this->assertSame('Complete', $indexes[0]['difference']);
        $this->assertSame('This index does not exist yet', $indexes[1]['documents']);
        $this->assertSame('Not reported', $indexes[1]['difference']);
        $this->assertSame('The engine did not answer for this index.', $indexes[2]['documents']);
    }

    #[Test]
    public function it_gives_the_total_size_in_megabytes_with_the_separator_of_the_reader(): void
    {
        $status = ['totalSizeInBytes' => 58126336];

        $this->assertSame('55.4 MB', $this->viewModel($status)->totalSize());
        $this->assertSame('55,4 MB', $this->viewModel($status, User::factory()->create(['number_format' => NumberFormatEnum::SpaceAndComma]))->totalSize());
        $this->assertNull($this->viewModel([])->totalSize());
    }

    #[Test]
    public function it_words_how_changes_reach_the_engine(): void
    {
        $queued = $this->viewModel(['prefix' => 'scranton_', 'isQueued' => true]);
        $direct = $this->viewModel([]);

        $this->assertSame('scranton_', $queued->prefix());
        $this->assertSame('Sent through the queue', $queued->queueLabel());
        $this->assertSame('None', $direct->prefix());
        $this->assertSame('Sent at once, as the record is saved', $direct->queueLabel());
    }

    #[Test]
    public function it_offers_the_password_to_an_administrator_who_has_one(): void
    {
        $this->assertTrue($this->viewModel([])->credentials()->offersPassword());
    }

    /** @param array<string, mixed> $status */
    private function viewModel(array $status, ?User $user = null): SearchEngineViewModel
    {
        return new SearchEngineViewModel(user: $user ?? User::factory()->create(['number_format' => NumberFormatEnum::CommaAndDot]), status: [
            'driver' => 'meilisearch',
            'health' => 'unavailable',
            'reason' => null,
            'responseTimeInMilliseconds' => null,
            'address' => null,
            'version' => null,
            'totalSizeInBytes' => null,
            'isIndexing' => null,
            'prefix' => '',
            'isQueued' => false,
            'indexes' => [],
            ...$status,
        ]);
    }

    /** @return array{name: string, kind: string, recordCount: int, documentCount: ?int, sizeInBytes: ?int, exists: ?bool} */
    private function index(string $kind, int $recordCount, ?int $documentCount = null, ?int $sizeInBytes = null, ?bool $exists = true): array
    {
        return [
            'name' => $kind,
            'kind' => $kind,
            'recordCount' => $recordCount,
            'documentCount' => $documentCount,
            'sizeInBytes' => $sizeInBytes,
            'exists' => $exists,
        ];
    }
}
