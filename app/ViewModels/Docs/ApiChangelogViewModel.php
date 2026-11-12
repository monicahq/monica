<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Carbon\CarbonImmutable;

class ApiChangelogViewModel
{
    public const array KINDS = ['Added', 'Changed', 'Deprecated', 'Removed'];

    private const array REQUIRED = ['id', 'kind', 'group', 'title', 'summary', 'why', 'changes'];

    private readonly string $directory;

    /** @var list<array{date: CarbonImmutable, note: string|null, latest: bool, count: int, countLabel: string, groups: list<array{name: string, entries: list<ApiChangelogEntry>}>}>|null */
    private ?array $releases = null;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? resource_path('docs/api-changelog');
    }

    /**
     * The releases a visitor may read, newest first. An entry is shown only while the reference
     * shows the part of the API it belongs to, and a release left with no entry is dropped.
     *
     * @return list<array{date: CarbonImmutable, note: string|null, latest: bool, count: int, countLabel: string, groups: list<array{name: string, entries: list<ApiChangelogEntry>}>}>
     */
    public function releases(): array
    {
        if ($this->releases !== null) {
            return $this->releases;
        }

        $shown = array_column(new ApiDocsViewModel()->groups(), 'name');
        $releases = [];

        foreach (array_reverse($this->files()) as $file) {
            $release = require $file;
            $date = CarbonImmutable::parse($release['date']);
            $groups = [];

            foreach ($release['entries'] as $entry) {
                if (! in_array($entry['group'], $shown, true)) {
                    continue;
                }

                $groups[$entry['group']][] = new ApiChangelogEntry($entry, $date);
            }

            if ($groups === []) {
                continue;
            }

            uksort($groups, fn (string $a, string $b): int => array_search($a, $shown, true) <=> array_search($b, $shown, true));

            $count = array_sum(array_map('count', $groups));

            $releases[] = [
                'date' => $date,
                'note' => $release['note'] ?? null,
                'latest' => $releases === [],
                'count' => $count,
                'countLabel' => self::countLabel($count),
                'groups' => array_map(
                    fn (string $name, array $entries): array => ['name' => $name, 'entries' => $entries],
                    array_keys($groups),
                    array_values($groups),
                ),
            ];
        }

        $this->releases = $releases;

        return $this->releases;
    }

    public function totalLabel(): string
    {
        return self::countLabel(count($this->entries()));
    }

    /**
     * The reference is written in English only, like the rest of the API documentation, so the
     * wording is chosen here rather than through a translated counted phrase.
     */
    private static function countLabel(int $count): string
    {
        return $count === 1 ? '1 change' : "{$count} changes";
    }

    public function entry(string $id): ?ApiChangelogEntry
    {
        foreach ($this->entries() as $entry) {
            if ($entry->id === $id) {
                return $entry;
            }
        }

        return null;
    }

    /** @return list<ApiChangelogEntry> */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->releases() as $release) {
            foreach ($release['groups'] as $group) {
                foreach ($group['entries'] as $entry) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /** @return list<ApiChangelogEntry> */
    public function sameRelease(ApiChangelogEntry $entry): array
    {
        return array_values(array_filter(
            $this->entries(),
            fn (ApiChangelogEntry $candidate): bool => $candidate->date->equalTo($entry->date) && $candidate->id !== $entry->id,
        ));
    }

    /**
     * Every defect in the written releases, one sentence each, naming the file and the entry. The
     * suite asserts the shipped folder has none, so a malformed entry cannot be merged.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $known = $this->knownGroups();
        $problems = [];
        $seen = [];

        foreach ($this->files() as $file) {
            $name = basename($file);
            $release = require $file;

            if (($release['date'] ?? null) !== basename($file, '.php')) {
                $problems[] = "{$name}: the date must equal the file name.";
            }

            if (($release['entries'] ?? []) === []) {
                $problems[] = "{$name}: a release needs at least one entry.";

                continue;
            }

            foreach ($release['entries'] as $position => $entry) {
                $label = "{$name}, entry ".($entry['id'] ?? "#{$position}");

                foreach (self::REQUIRED as $field) {
                    if (($entry[$field] ?? null) === null || $entry[$field] === '' || $entry[$field] === []) {
                        $problems[] = "{$label}: {$field} is missing.";
                    }
                }

                if (isset($entry['id'])) {
                    if (preg_match('/^[a-z0-9-]+$/', $entry['id']) !== 1) {
                        $problems[] = "{$label}: the id may hold only lower case letters, digits and hyphens.";
                    }

                    if (in_array($entry['id'], $seen, true)) {
                        $problems[] = "{$label}: another entry already uses this id.";
                    }

                    $seen[] = $entry['id'];
                }

                if (isset($entry['kind']) && ! in_array($entry['kind'], self::KINDS, true)) {
                    $problems[] = "{$label}: the kind must be Added, Changed, Deprecated or Removed.";
                }

                if (isset($entry['group']) && ! in_array($entry['group'], $known, true)) {
                    $problems[] = "{$label}: the group must be the name of a file in resources/docs/api.";
                }

                if (($entry['breaking'] ?? false) && ($entry['impact'] ?? '') === '') {
                    $problems[] = "{$label}: a breaking entry needs an impact.";
                }

                foreach ($entry['changes'] ?? [] as $change) {
                    if (! in_array($change['change'] ?? null, self::KINDS, true)) {
                        $problems[] = "{$label}: the change of a field must be Added, Changed, Deprecated or Removed.";
                    }
                }

                if (isset($entry['example']) && ($entry['example']['after'] ?? '') === '') {
                    $problems[] = "{$label}: an example needs an after shape.";
                }
            }
        }

        return $problems;
    }

    public function markdown(?ApiChangelogEntry $entry = null): string
    {
        $markdown = new ApiChangelogMarkdown($this);

        return $entry === null ? $markdown->list() : $markdown->entry($entry);
    }

    /** @return list<string> */
    private function files(): array
    {
        $files = glob($this->directory.'/*.php') ?: [];
        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function knownGroups(): array
    {
        $files = glob(resource_path('docs/api').'/*.php') ?: [];

        return array_map(fn (string $file): string => (require $file)['name'], $files);
    }
}
