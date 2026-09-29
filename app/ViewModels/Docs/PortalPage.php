<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

class PortalPage
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $section,
        public readonly string $locale,
        public readonly string $relativePath,
        public readonly string $path,
        public readonly int $sectionOrder,
        public readonly int $pageOrder,
        public readonly ?string $lastUpdated = null,
    ) {}

    public function isRoot(): bool
    {
        return $this->sectionOrder === 0;
    }

    public function isSectionIndex(): bool
    {
        return ! $this->isRoot() && $this->slug === $this->section;
    }

    public function url(): string
    {
        if ($this->isRoot()) {
            return route('docs.portal.index', ['locale' => $this->locale]);
        }

        return route('docs.portal.show', [
            'locale' => $this->locale,
            'section' => $this->section,
            'slug' => $this->isSectionIndex() ? null : $this->slug,
        ]);
    }

    public function markdownUrl(): string
    {
        if ($this->isRoot()) {
            return route('docs.portal.markdown.index', ['locale' => $this->locale]);
        }

        if ($this->isSectionIndex()) {
            return route('docs.portal.markdown.section', ['locale' => $this->locale, 'section' => $this->section]);
        }

        return route('docs.portal.markdown.show', [
            'locale' => $this->locale,
            'section' => $this->section,
            'slug' => $this->slug,
        ]);
    }

    public function contents(): string
    {
        return (string) file_get_contents($this->path);
    }
}
