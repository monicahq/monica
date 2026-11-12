<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\NumberFormatEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\StorageSize;
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;

class SearchEngineViewModel
{
    /**
     * @param array{
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
     * } $status
     */
    public function __construct(
        private readonly User $user,
        private readonly array $status,
    ) {}

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    public function engineName(): string
    {
        return match ($this->status['driver']) {
            'database' => __('The database of this instance'),
            'meilisearch' => 'Meilisearch',
            'typesense' => 'Typesense',
            'algolia' => 'Algolia',
            default => $this->status['driver'],
        };
    }

    public function healthLabel(): string
    {
        return match ($this->status['health']) {
            'healthy' => __('Healthy'),
            'unreachable' => __('Unreachable'),
            default => __('No status available'),
        };
    }

    public function healthTone(): string
    {
        return match ($this->status['health']) {
            'healthy' => 'success',
            'unreachable' => 'danger',
            default => 'neutral',
        };
    }

    public function healthNote(): ?string
    {
        return match ($this->status['health']) {
            'healthy' => null,
            'unreachable' => $this->status['reason'],
            default => __('This screen does not know how to question this engine.'),
        };
    }

    public function responseTime(): ?string
    {
        if ($this->status['responseTimeInMilliseconds'] === null) {
            return null;
        }

        return trans_choice('[0,*]:count ms', $this->status['responseTimeInMilliseconds']);
    }

    public function address(): ?string
    {
        return $this->status['address'];
    }

    public function version(): ?string
    {
        return $this->status['version'];
    }

    public function totalSize(): ?string
    {
        if ($this->status['totalSizeInBytes'] === null) {
            return null;
        }

        $megabytes = $this->status['totalSizeInBytes'] / 1_048_576;
        $format = $this->user->number_format
            ?? NumberFormatEnum::tryFrom((string) AvailableLocales::details(app()->getLocale())['number_format'])
            ?? NumberFormatEnum::CommaAndDot;

        return trans_choice('[0,*]:count MB', $megabytes, ['count' => $format->format($megabytes, 1)]);
    }

    public function typingMistakesNote(): ?string
    {
        if ($this->status['driver'] !== 'database') {
            return null;
        }

        return __('This engine does not tolerate typing mistakes. Algolia, Meilisearch and Typesense do.');
    }

    public function canRebuild(): bool
    {
        return $this->status['health'] === 'healthy';
    }

    /** @return list<array{label: string, name: string, documents: string, records: int, difference: string, size: string, rebuildUrl: string}> */
    public function indexes(): array
    {
        return array_map(fn (array $index): array => [
            'label' => match ($index['kind']) {
                'contacts' => __('Contacts'),
                'entities' => __('Entities'),
                default => __('Life events'),
            },
            'name' => $index['name'],
            'documents' => $this->documents($index),
            'records' => $index['recordCount'],
            'difference' => $this->difference($index),
            'size' => $index['sizeInBytes'] === null ? __('Not reported') : StorageSize::readable($index['sizeInBytes']),
            'rebuildUrl' => route('admin.search.rebuild.create', ['kind' => $index['kind']]),
        ], $this->status['indexes']);
    }

    public function prefix(): string
    {
        return $this->status['prefix'] === '' ? __('None') : $this->status['prefix'];
    }

    public function queueLabel(): string
    {
        return $this->status['isQueued']
            ? __('Sent through the queue')
            : __('Sent at once, as the record is saved');
    }

    public function indexingLabel(): ?string
    {
        if ($this->status['isIndexing'] === null) {
            return null;
        }

        return $this->status['isIndexing']
            ? __('Yes, the engine is working through what it received')
            : __('No, the engine has nothing waiting');
    }

    /** @param array{documentCount: ?int, exists: ?bool} $index */
    private function documents(array $index): string
    {
        if ($index['documentCount'] !== null) {
            return (string) $index['documentCount'];
        }

        if ($index['exists'] === false) {
            return __('This index does not exist yet');
        }

        if ($this->status['health'] === 'healthy') {
            return __('The engine did not answer for this index.');
        }

        return __('Not reported');
    }

    /** @param array{recordCount: int, documentCount: ?int} $index */
    private function difference(array $index): string
    {
        if ($index['documentCount'] === null) {
            return __('Not reported');
        }

        $missing = $index['recordCount'] - $index['documentCount'];

        if ($missing === 0) {
            return __('Complete');
        }

        return $missing > 0
            ? trans_choice(':count record missing|:count records missing', $missing)
            : trans_choice(':count document left over|:count documents left over', -$missing);
    }
}
