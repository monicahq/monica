<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

/**
 * The API changelog written as Markdown, the list at `/docs/api/changelog.md` and one entry at
 * `/docs/api/changelog/{entry}.md`, for a reader that is not a browser.
 */
class ApiChangelogMarkdown
{
    public const string INTRODUCTION = 'Every change to the API, grouped by the date it shipped. Changes that can stop an existing integration from working are marked as breaking, with what to look out for.';

    public const string EMPTY = 'No change has been recorded yet.';

    public function __construct(
        private readonly ApiChangelogViewModel $viewModel,
    ) {}

    public function list(): string
    {
        $blocks = ['# API changelog', self::INTRODUCTION];
        $releases = $this->viewModel->releases();

        if ($releases === []) {
            $blocks[] = self::EMPTY;
        }

        foreach ($releases as $release) {
            $blocks[] = '## '.$release['date']->format('j F Y').($release['latest'] ? ' (latest)' : '');

            if ($release['note'] !== null) {
                $blocks[] = $release['note'];
            }

            foreach ($release['groups'] as $group) {
                $rows = array_map(
                    fn (ApiChangelogEntry $entry): string => "- {$entry->kind}: [{$entry->title}](".route('docs.api.changelog.markdown.show', ['entry' => $entry->id]).')'.($entry->breaking ? '. Breaking.' : ''),
                    $group['entries'],
                );

                $blocks[] = '### '.$group['name'];
                $blocks[] = implode("\n", $rows);
            }
        }

        return implode("\n\n", $blocks)."\n";
    }

    public function entry(ApiChangelogEntry $entry): string
    {
        $meta = [$entry->date->format('j F Y'), $entry->kind, $entry->group];

        if ($entry->breaking) {
            $meta[] = 'Breaking';
        }

        $blocks = ['# '.$entry->title, implode(' · ', $meta), $entry->summary];

        if ($entry->breaking) {
            $blocks[] = '## Why this is a breaking change';
            $blocks[] = (string) $entry->impact;
        }

        $blocks[] = '## Why it changed';
        $blocks[] = $entry->why;

        $rows = [];

        foreach ($entry->changes as $position => $change) {
            $endpoints = array_map(fn (array $endpoint): string => "`{$endpoint['method']} /api/v1{$endpoint['path']}`", $entry->endpointsOf($position));
            $rows[] = "- `{$change['field']}` ({$change['change']}): ".($endpoints === [] ? 'All endpoints' : implode(', ', $endpoints));
        }

        $blocks[] = '## Changes';
        $blocks[] = implode("\n", $rows);

        $siblings = $this->viewModel->sameRelease($entry);

        if ($siblings !== []) {
            $blocks[] = '## In the same release';
            $blocks[] = implode("\n", array_map(
                fn (ApiChangelogEntry $sibling): string => "- {$sibling->kind}: [{$sibling->title}](".route('docs.api.changelog.markdown.show', ['entry' => $sibling->id]).')',
                $siblings,
            ));
        }

        if ($entry->example !== null) {
            $blocks[] = '## Example';

            if (isset($entry->example['label'])) {
                $blocks[] = $entry->example['label'];
            }

            if ($entry->hasBefore()) {
                $blocks[] = "Before:\n\n```\n{$entry->example['before']}\n```";
            }

            $blocks[] = ($entry->hasBefore() ? "After:\n\n" : '')."```\n{$entry->example['after']}\n```";
        }

        return implode("\n\n", $blocks)."\n";
    }
}
