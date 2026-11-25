<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ChangelogEntryTypeEnum;
use App\Models\ChangelogComment;
use App\Models\ChangelogSeenEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the entries of `changelog/`, one Markdown file each. The front matter is a run of flat
 * `key: value` lines, which is all the format allows, so no YAML parser is needed. The parsed list
 * is cached under a key made of the file names and their modification times, screenshots included,
 * so a deployment that adds or edits a file is read again without any command.
 */
class ChangelogEntries
{
    public const string ULID_PATTERN = '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/';

    public const string LINK_PATTERN = '#^/(?![/\\\\])\S*$#';

    public const int MAX_SCREENSHOTS = 6;

    public const int MAX_SCREENSHOT_BYTES = 1048576;

    public function __construct(
        private readonly ?string $directory = null,
        private readonly ?string $screenshotDirectory = null,
    ) {}

    /**
     * Every valid entry, future ones included, newest first.
     *
     * @return list<ChangelogEntry>
     */
    public function all(): array
    {
        $files = $this->files();
        $signature = implode('|', array_map(fn (string $file): string => $file.':'.filemtime($file), [...$files, ...$this->screenshotFiles()]));

        // The cache refuses to unserialize objects (cache.serializable_classes), so it holds plain arrays.
        $rows = Cache::rememberForever('changelog:'.md5($signature), fn (): array => array_map(fn (ChangelogEntry $entry): array => [
            'id' => $entry->id,
            'title' => $entry->title,
            'type' => $entry->type->value,
            'area' => $entry->area,
            'publishedAt' => $entry->publishedAt->toIso8601String(),
            'link' => $entry->link,
            'body' => $entry->body,
            'file' => $entry->file,
            'screenshots' => array_map(fn (ChangelogScreenshot $screenshot): array => [
                'position' => $screenshot->position,
                'description' => $screenshot->description,
                'file' => $screenshot->file,
                'width' => $screenshot->width,
                'height' => $screenshot->height,
            ], $entry->screenshots),
        ], $this->parse($files)['entries']));

        return array_map(fn (array $row): ChangelogEntry => new ChangelogEntry(
            id: $row['id'],
            title: $row['title'],
            type: ChangelogEntryTypeEnum::from($row['type']),
            area: $row['area'],
            publishedAt: CarbonImmutable::parse($row['publishedAt'])->utc(),
            link: $row['link'],
            body: $row['body'],
            file: $row['file'],
            screenshots: array_map(fn (array $screenshot): ChangelogScreenshot => new ChangelogScreenshot(
                position: $screenshot['position'],
                description: $screenshot['description'],
                file: $screenshot['file'],
                width: $screenshot['width'],
                height: $screenshot['height'],
            ), $row['screenshots']),
        ), $rows);
    }

    /**
     * The entries a reader may see: valid and already published.
     *
     * @return list<ChangelogEntry>
     */
    public function visible(): array
    {
        $now = CarbonImmutable::now();

        return array_values(array_filter($this->all(), fn (ChangelogEntry $entry): bool => $entry->publishedAt->lessThanOrEqualTo($now)));
    }

    public function find(string $id): ?ChangelogEntry
    {
        foreach ($this->visible() as $entry) {
            if ($entry->id === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * The number of days a reader asked to look back over: 90 when asked for, 30 otherwise.
     */
    public static function period(?string $requested): int
    {
        return $requested === '90' ? 90 : 30;
    }

    /**
     * The visible entries of the last days, in the order the screen shows them: UTC days newest
     * first, and within a day features and improvements before fixes, each newest first.
     *
     * @return list<ChangelogEntry>
     */
    public function publishedWithin(int $days): array
    {
        $since = CarbonImmutable::now('UTC')->subDays($days);
        $entries = array_values(array_filter($this->visible(), fn (ChangelogEntry $entry): bool => $entry->publishedAt->greaterThanOrEqualTo($since)));

        usort($entries, fn (ChangelogEntry $a, ChangelogEntry $b): int => [$b->day(), $a->isFix(), $b->publishedAt->getTimestamp()]
            <=> [$a->day(), $b->isFix(), $a->publishedAt->getTimestamp()]);

        return $entries;
    }

    /**
     * @param  list<ChangelogEntry>  $entries
     * @return array{days: int, features: int, improvements: int, fixes: int}
     */
    public static function figures(array $entries): array
    {
        $countOf = fn (ChangelogEntryTypeEnum $type): int => count(array_filter($entries, fn (ChangelogEntry $entry): bool => $entry->type === $type));

        return [
            'days' => count(array_unique(array_map(fn (ChangelogEntry $entry): string => $entry->day(), $entries))),
            'features' => $countOf(ChangelogEntryTypeEnum::Feature),
            'improvements' => $countOf(ChangelogEntryTypeEnum::Improvement),
            'fixes' => $countOf(ChangelogEntryTypeEnum::Fix),
        ];
    }

    /**
     * How many comments everybody can read under each entry.
     *
     * @param  list<string>  $ids
     * @return array<string, int>
     */
    public static function commentCounts(array $ids): array
    {
        return ChangelogComment::query()
            ->shownToEverybody()
            ->whereIn('entry_id', $ids)
            ->selectRaw('entry_id, count(*) as aggregate')
            ->groupBy('entry_id')
            ->pluck('aggregate', 'entry_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * The visible entries a person has not seen yet. Entries published before the person joined
     * are never counted: somebody new is not greeted with the whole history.
     *
     * @return list<ChangelogEntry>
     */
    public function unseenBy(User $user): array
    {
        $candidates = array_values(array_filter($this->visible(), fn (ChangelogEntry $entry): bool => $entry->publishedAt->greaterThan($user->created_at)));

        if ($candidates === []) {
            return [];
        }

        $seen = ChangelogSeenEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('entry_id', array_map(fn (ChangelogEntry $entry): string => $entry->id, $candidates))
            ->pluck('entry_id')
            ->flip();

        return array_values(array_filter($candidates, fn (ChangelogEntry $entry): bool => ! $seen->has($entry->id)));
    }

    /**
     * What is wrong with the files, one sentence per defect, naming the file.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        return $this->parse($this->files())['problems'];
    }

    /** @return list<string> */
    private function files(): array
    {
        $files = glob(($this->directory ?? base_path('changelog')).'/*.md') ?: [];

        sort($files, SORT_STRING);

        return $files;
    }

    private function screenshotDirectory(): string
    {
        return $this->screenshotDirectory ?? public_path('changelog');
    }

    /** @return list<string> */
    private function screenshotFiles(): array
    {
        $files = glob($this->screenshotDirectory().'/*') ?: [];

        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param  list<string>  $files
     * @return array{entries: list<ChangelogEntry>, problems: list<string>}
     */
    private function parse(array $files): array
    {
        $entries = [];
        $problems = [];
        $seen = [];
        $declared = [];

        foreach ($files as $file) {
            $name = basename($file);
            $entry = $this->read($file, $name, $problems, $declared);

            if ($entry === null) {
                continue;
            }

            if (isset($seen[$entry->id])) {
                $problems[] = "{$name}: the id {$entry->id} is already used by {$seen[$entry->id]}.";

                continue;
            }

            $seen[$entry->id] = $name;
            $entries[] = $entry;
        }

        foreach ($this->screenshotFiles() as $file) {
            if (! isset($declared[basename($file)])) {
                $problems[] = basename($file).': no entry declares this screenshot.';
            }
        }

        usort($entries, fn (ChangelogEntry $a, ChangelogEntry $b): int => $b->publishedAt->getTimestamp() <=> $a->publishedAt->getTimestamp());

        return ['entries' => $entries, 'problems' => $problems];
    }

    /**
     * @param  list<string>  $problems
     * @param  array<string, true>  $declared
     */
    private function read(string $file, string $name, array &$problems, array &$declared): ?ChangelogEntry
    {
        if (! preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', (string) file_get_contents($file), $matches)) {
            $problems[] = "{$name}: the front matter is missing.";

            return null;
        }

        $fields = [];

        foreach (preg_split('/\R/', $matches[1]) ?: [] as $line) {
            if (mb_trim($line) === '') {
                continue;
            }

            if (! str_contains($line, ':')) {
                $problems[] = "{$name}: the line \"{$line}\" is not written as key: value.";

                return null;
            }

            [$key, $value] = explode(':', $line, 2);
            $fields[mb_trim($key)] = $this->unquote(mb_trim($value));
        }

        $body = mb_trim($matches[2]);
        $type = ChangelogEntryTypeEnum::tryFrom($fields['type'] ?? '');
        $publishedAt = $fields['published_at'] ?? '';
        $before = count($problems);

        if (! preg_match(self::ULID_PATTERN, $fields['id'] ?? '')) {
            $problems[] = "{$name}: id must be a ULID of 26 characters.";
        }

        if (($fields['title'] ?? '') === '') {
            $problems[] = "{$name}: title is required.";
        }

        if (! $type instanceof ChangelogEntryTypeEnum) {
            $problems[] = "{$name}: type must be ".implode(', ', ChangelogEntryTypeEnum::values()).'.';
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?Z$/', $publishedAt)) {
            $problems[] = "{$name}: published_at must be an ISO 8601 date in UTC, such as 2026-11-17T10:00:00Z.";
        }

        if ($body === '') {
            $problems[] = "{$name}: the description under the front matter is empty.";
        }

        if (count($problems) > $before) {
            return null;
        }

        $link = $fields['link'] ?? null;

        if ($link !== null && ! preg_match(self::LINK_PATTERN, $link)) {
            $problems[] = "{$name}: link must be a path inside the application, starting with a single /.";
            $link = null;
        }

        return new ChangelogEntry(
            id: $fields['id'],
            title: $fields['title'],
            type: ChangelogEntryTypeEnum::from($fields['type']),
            area: ($fields['area'] ?? '') === '' ? null : $fields['area'],
            publishedAt: CarbonImmutable::parse($publishedAt, 'UTC'),
            link: $link,
            body: $body,
            file: $name,
            screenshots: $this->screenshots($fields, $name, $problems, $declared),
        );
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $problems
     * @param  array<string, true>  $declared
     * @return list<ChangelogScreenshot>
     */
    private function screenshots(array $fields, string $name, array &$problems, array &$declared): array
    {
        $descriptions = [];

        foreach ($fields as $key => $value) {
            if (preg_match('/^screenshot_(\d+)$/', $key, $matches)) {
                $descriptions[(int) $matches[1]] = $value;
            }
        }

        if ($descriptions === []) {
            return [];
        }

        ksort($descriptions);
        $before = count($problems);

        if (count($descriptions) > self::MAX_SCREENSHOTS) {
            $problems[] = "{$name}: an entry carries at most ".self::MAX_SCREENSHOTS.' screenshots.';
        }

        if (array_keys($descriptions) !== range(1, count($descriptions))) {
            $problems[] = "{$name}: screenshots must be numbered from 1 without a gap.";
        }

        $screenshots = [];

        foreach ($descriptions as $position => $description) {
            $file = "{$fields['id']}-{$position}.webp";
            $path = $this->screenshotDirectory()."/{$file}";
            $declared[$file] = true;

            if ($description === '') {
                $problems[] = "{$name}: screenshot_{$position} needs a description.";

                continue;
            }

            if (! is_file($path)) {
                $problems[] = "{$name}: the file {$file} of screenshot_{$position} is missing.";

                continue;
            }

            if (filesize($path) > self::MAX_SCREENSHOT_BYTES) {
                $problems[] = "{$name}: the file {$file} is over 1 MB.";

                continue;
            }

            $size = filesize($path) === 0 ? false : getimagesize($path);

            if ($size === false || $size[2] !== IMAGETYPE_WEBP) {
                $problems[] = "{$name}: the file {$file} is not a valid WebP image.";

                continue;
            }

            $screenshots[] = new ChangelogScreenshot(
                position: $position,
                description: $description,
                file: $file,
                width: $size[0],
                height: $size[1],
            );
        }

        return count($problems) > $before ? [] : $screenshots;
    }

    private function unquote(string $value): string
    {
        if (mb_strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && str_ends_with($value, $value[0])) {
            return mb_substr($value, 1, -1);
        }

        return $value;
    }
}
