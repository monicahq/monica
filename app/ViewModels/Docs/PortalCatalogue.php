<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Symfony\Component\Finder\Finder;

class PortalCatalogue
{
    /** @var list<PortalPage>|null */
    private ?array $pages = null;

    public function __construct(
        public readonly string $locale,
    ) {}

    public static function directory(): string
    {
        return base_path('docs/portal');
    }

    /** @return list<string> */
    public static function locales(): array
    {
        $directories = glob(self::directory().'/*', GLOB_ONLYDIR) ?: [];

        return array_map(basename(...), $directories);
    }

    /** @return array{id: string, title: string, slug: string, section: string}|null */
    public static function frontmatter(string $contents): ?array
    {
        if (! preg_match('/^---\n(.*?)\n---\n/s', $contents, $matches)) {
            return null;
        }

        $values = [];

        foreach (explode("\n", $matches[1]) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $values[trim($key)] = trim(trim($value), '"');
        }

        foreach (['id', 'title', 'slug', 'section'] as $key) {
            if (($values[$key] ?? '') === '') {
                return null;
            }
        }

        return [
            'id' => $values['id'],
            'title' => $values['title'],
            'slug' => $values['slug'],
            'section' => $values['section'],
        ];
    }

    public function exists(): bool
    {
        return in_array($this->locale, self::locales(), true);
    }

    /** @return list<PortalPage> */
    public function pages(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        if (! $this->exists()) {
            return $this->pages = [];
        }

        $files = Finder::create()
            ->files()
            ->in(self::directory()."/{$this->locale}")
            ->name('*.md')
            ->depth('< 2');

        $pages = [];

        foreach ($files as $file) {
            $frontmatter = self::frontmatter($file->getContents());

            if ($frontmatter === null) {
                continue;
            }

            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
            $folder = str_contains($relativePath, '/') ? explode('/', $relativePath)[0] : null;

            $pages[] = new PortalPage(
                id: $frontmatter['id'],
                title: $frontmatter['title'],
                slug: $frontmatter['slug'],
                section: $frontmatter['section'],
                locale: $this->locale,
                relativePath: $relativePath,
                path: $file->getPathname(),
                sectionOrder: $folder === null ? 0 : $this->order($folder),
                pageOrder: $this->order($file->getFilename()),
            );
        }

        usort($pages, fn (PortalPage $a, PortalPage $b): int => [$a->sectionOrder, $a->pageOrder] <=> [$b->sectionOrder, $b->pageOrder]);

        return $this->pages = $pages;
    }

    public function root(): ?PortalPage
    {
        foreach ($this->pages() as $page) {
            if ($page->isRoot()) {
                return $page;
            }
        }

        return null;
    }

    public function find(string $section, ?string $slug = null): ?PortalPage
    {
        foreach ($this->pages() as $page) {
            if ($page->isRoot() || $page->section !== $section) {
                continue;
            }

            if ($page->slug === ($slug ?? $section)) {
                return $page;
            }
        }

        return null;
    }

    public function page(string $id): ?PortalPage
    {
        foreach ($this->pages() as $page) {
            if ($page->id === $id) {
                return $page;
            }
        }

        return null;
    }

    /** @return list<array{index: PortalPage, pages: list<PortalPage>}> */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->pages() as $page) {
            if ($page->isRoot()) {
                continue;
            }

            if ($page->isSectionIndex()) {
                $sections[$page->section] = ['index' => $page, 'pages' => []];

                continue;
            }

            if (isset($sections[$page->section])) {
                $sections[$page->section]['pages'][] = $page;
            }
        }

        return array_values($sections);
    }

    public function previous(PortalPage $page): ?PortalPage
    {
        $pages = $this->pages();
        $position = array_search($page, $pages, true);

        return $position === false || $position === 0 ? null : $pages[$position - 1];
    }

    public function next(PortalPage $page): ?PortalPage
    {
        $pages = $this->pages();
        $position = array_search($page, $pages, true);

        return $position === false ? null : $pages[$position + 1] ?? null;
    }

    private function order(string $name): int
    {
        return preg_match('/^(\d+)-/', $name, $matches) ? (int) $matches[1] : 0;
    }
}
