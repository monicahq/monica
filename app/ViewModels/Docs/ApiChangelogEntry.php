<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Carbon\CarbonImmutable;

class ApiChangelogEntry
{
    public readonly string $id;

    public readonly string $kind;

    public readonly string $group;

    public readonly string $title;

    public readonly string $summary;

    public readonly string $why;

    public readonly bool $breaking;

    public readonly ?string $impact;

    /** @var list<array{field: string, change: string, endpoints: list<string>}> */
    public readonly array $changes;

    /** @var array{label?: string, before?: string, after: string}|null */
    public readonly ?array $example;

    /** @param  array<string, mixed>  $entry */
    public function __construct(
        array $entry,
        public readonly CarbonImmutable $date,
    ) {
        $this->id = $entry['id'];
        $this->kind = $entry['kind'];
        $this->group = $entry['group'];
        $this->title = $entry['title'];
        $this->summary = $entry['summary'];
        $this->why = $entry['why'];
        $this->breaking = $entry['breaking'] ?? false;
        $this->impact = $entry['impact'] ?? null;
        $this->changes = $entry['changes'];
        $this->example = $entry['example'] ?? null;
    }

    /**
     * Every endpoint the entry names, once each, in the order first named. An entry naming none
     * concerns every endpoint, such as a change to the shape of an error.
     *
     * @return list<array{method: string, path: string}>
     */
    public function endpoints(): array
    {
        $endpoints = [];

        foreach ($this->changes as $change) {
            foreach ($change['endpoints'] as $endpoint) {
                [$method, $path] = explode(' ', $endpoint, 2);
                $endpoints["{$method} {$path}"] = ['method' => $method, 'path' => $path];
            }
        }

        return array_values($endpoints);
    }

    /** @return list<array{method: string, path: string}> */
    public function endpointsOf(int $position): array
    {
        return array_map(function (string $endpoint): array {
            [$method, $path] = explode(' ', $endpoint, 2);

            return ['method' => $method, 'path' => $path];
        }, $this->changes[$position]['endpoints']);
    }

    public function endpointCount(): string
    {
        $count = count($this->endpoints());

        if ($count === 0) {
            return 'All endpoints';
        }

        return $count === 1 ? '1 endpoint' : "{$count} endpoints";
    }

    public function hasBefore(): bool
    {
        return isset($this->example['before']);
    }
}
