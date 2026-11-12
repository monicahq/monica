<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Scout\EngineManager;
use Throwable;

/**
 * Ask the search engine this instance is configured to use whether it answers and what each index
 * holds, beside what the instance itself holds. It changes nothing, here or in the engine.
 */
class CheckSearchEngine
{
    public const array INDEXES = [
        'contacts' => Contact::class,
        'entities' => Entity::class,
        'life-events' => LifeEvent::class,
    ];

    /**
     * @return array{
     *     driver: string,
     *     health: string,
     *     reason: ?string,
     *     responseTimeInMilliseconds: ?int,
     *     address: ?string,
     *     version: ?string,
     *     totalSizeInBytes: ?int,
     *     isIndexing: ?bool,
     *     prefix: string,
     *     isQueued: bool,
     *     indexes: list<array{name: string, kind: string, recordCount: int, documentCount: ?int, sizeInBytes: ?int, exists: ?bool}>
     * }
     */
    public function execute(): array
    {
        $status = $this->baseline();

        if (! in_array($status['driver'], ['database', 'meilisearch', 'typesense', 'algolia'], true)) {
            return $status;
        }

        try {
            resolve(EngineManager::class)->engine();
        } catch (Throwable $exception) {
            return [...$status, 'health' => 'unreachable', 'reason' => $this->withoutCredentials($exception->getMessage())];
        }

        return match ($status['driver']) {
            'database' => $this->askDatabase($status),
            'meilisearch' => $this->askMeilisearch($status),
            'typesense' => $this->askTypesense($status),
            'algolia' => $this->askAlgolia($status),
        };
    }

    /** @return array<string, mixed> */
    private function baseline(): array
    {
        $indexes = [];

        foreach (self::INDEXES as $kind => $model) {
            $indexes[] = [
                'name' => new $model()->searchableAs(),
                'kind' => $kind,
                'recordCount' => $model::query()->count(),
                'documentCount' => null,
                'sizeInBytes' => null,
                'exists' => null,
            ];
        }

        return [
            'driver' => (string) config('scout.driver'),
            'health' => 'unavailable',
            'reason' => null,
            'responseTimeInMilliseconds' => null,
            'address' => null,
            'version' => null,
            'totalSizeInBytes' => null,
            'isIndexing' => null,
            'prefix' => (string) config('scout.prefix'),
            'isQueued' => (bool) config('scout.queue'),
            'indexes' => $indexes,
        ];
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    private function askDatabase(array $status): array
    {
        $found = [];

        foreach (self::INDEXES as $model) {
            $found[new $model()->searchableAs()] = [
                'documentCount' => $model::query()->whereNotNull('search_name')->count(),
                'sizeInBytes' => null,
            ];
        }

        return $this->withIndexes([...$status, 'health' => 'healthy'], $found);
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    private function askMeilisearch(array $status): array
    {
        $host = mb_rtrim((string) config('scout.meilisearch.host'), '/');
        $headers = filled(config('scout.meilisearch.key')) ? ['Authorization' => 'Bearer '.config('scout.meilisearch.key')] : [];

        [$status, $health] = $this->askHealth([...$status, 'address' => parse_url($host, PHP_URL_HOST) ?: $host], "{$host}/health", $headers);

        if ($health === null) {
            return $status;
        }

        $answers = $this->askTogether($headers, ['version' => "{$host}/version", 'stats' => "{$host}/stats"]);
        $status['version'] = $answers['version']?->json('pkgVersion');

        if ($answers['stats'] === null) {
            return $this->withIndexes($status, null);
        }

        $status['totalSizeInBytes'] = is_int($answers['stats']->json('databaseSize')) ? $answers['stats']->json('databaseSize') : null;
        $found = [];
        $isIndexing = false;

        foreach ((array) $answers['stats']->json('indexes', []) as $name => $index) {
            $found[$name] = [
                'documentCount' => (int) ($index['numberOfDocuments'] ?? 0),
                'sizeInBytes' => isset($index['rawDocumentDbSize']) ? (int) $index['rawDocumentDbSize'] : null,
            ];

            if (in_array($name, array_column($status['indexes'], 'name'), true) && ($index['isIndexing'] ?? false) === true) {
                $isIndexing = true;
            }
        }

        return $this->withIndexes([...$status, 'isIndexing' => $isIndexing], $found);
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    private function askTypesense(array $status): array
    {
        $node = (array) config('scout.typesense.client-settings.nodes.0', []);
        $host = "{$node['protocol']}://{$node['host']}:{$node['port']}{$node['path']}";
        $headers = ['X-TYPESENSE-API-KEY' => (string) config('scout.typesense.client-settings.api_key')];

        [$status, $health] = $this->askHealth([...$status, 'address' => $node['host']], "{$host}/health", $headers);

        if ($health === null) {
            return $status;
        }

        $answers = $this->askTogether($headers, ['version' => "{$host}/debug", 'collections' => "{$host}/collections"]);
        $status['version'] = $answers['version']?->json('version');

        if ($answers['collections'] === null) {
            return $this->withIndexes($status, null);
        }

        $found = [];

        foreach ((array) $answers['collections']->json() as $collection) {
            $found[$collection['name']] = ['documentCount' => (int) ($collection['num_documents'] ?? 0), 'sizeInBytes' => null];
        }

        return $this->withIndexes($status, $found);
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    private function askAlgolia(array $status): array
    {
        $address = config('scout.algolia.id').'.algolia.net';

        [$status, $indexes] = $this->askHealth([...$status, 'address' => $address], "https://{$address}/1/indexes", [
            'X-Algolia-Application-Id' => (string) config('scout.algolia.id'),
            'X-Algolia-API-Key' => (string) config('scout.algolia.secret'),
        ]);

        if ($indexes === null) {
            return $status;
        }

        $status['totalSizeInBytes'] = (int) collect((array) $indexes->json('items', []))->sum('dataSize');
        $found = [];
        $isIndexing = false;

        foreach ((array) $indexes->json('items', []) as $index) {
            $found[$index['name']] = [
                'documentCount' => (int) ($index['entries'] ?? 0),
                'sizeInBytes' => isset($index['dataSize']) ? (int) $index['dataSize'] : null,
            ];

            if (in_array($index['name'], array_column($status['indexes'], 'name'), true) && ($index['pendingTask'] ?? false) === true) {
                $isIndexing = true;
            }
        }

        return $this->withIndexes([...$status, 'isIndexing' => $isIndexing], $found);
    }

    /**
     * @param  array<string, mixed>  $status
     * @param  array<string, string>  $headers
     * @return array{0: array<string, mixed>, 1: ?Response}
     */
    private function askHealth(array $status, string $url, array $headers): array
    {
        $started = microtime(true);

        try {
            $response = Http::withHeaders($headers)->connectTimeout(1)->timeout(2)->get($url);
        } catch (ConnectionException $exception) {
            return [[...$status, 'health' => 'unreachable', 'reason' => $this->withoutCredentials($exception->getMessage())], null];
        }

        $status['responseTimeInMilliseconds'] = (int) round((microtime(true) - $started) * 1000);

        if ($response->failed()) {
            return [[...$status, 'health' => 'unreachable', 'reason' => "HTTP {$response->status()}"], null];
        }

        return [[...$status, 'health' => 'healthy'], $response];
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $urls
     * @return array<string, ?Response>
     */
    private function askTogether(array $headers, array $urls): array
    {
        $responses = Http::pool(function (Pool $pool) use ($headers, $urls): array {
            $requests = [];

            foreach ($urls as $name => $url) {
                $requests[] = $pool->as($name)->withHeaders($headers)->connectTimeout(1)->timeout(2)->get($url);
            }

            return $requests;
        });

        return array_map(
            fn (mixed $response): ?Response => $response instanceof Response && $response->successful() ? $response : null,
            $responses,
        );
    }

    /**
     * @param  array<string, mixed>  $status
     * @param  array<string, array{documentCount: int, sizeInBytes: ?int}>|null  $found
     * @return array<string, mixed>
     */
    private function withIndexes(array $status, ?array $found): array
    {
        foreach ($status['indexes'] as $position => $index) {
            if ($found === null) {
                continue;
            }

            if (! isset($found[$index['name']])) {
                $status['indexes'][$position]['exists'] = false;

                continue;
            }

            $status['indexes'][$position] = [...$index, ...$found[$index['name']], 'exists' => true];
        }

        return $status;
    }

    private function withoutCredentials(string $message): string
    {
        $credentials = array_filter([
            (string) config('scout.meilisearch.key'),
            (string) config('scout.typesense.client-settings.api_key'),
            (string) config('scout.algolia.secret'),
        ]);

        return str_replace($credentials, '***', $message);
    }
}
