<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

class PortalDocsViewModel
{
    public const string SOURCE_URL = 'https://github.com/monicahq/monica';

    /** @var array{lead: ?string, blocks: list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>, contents: list<array{id: string, label: string}>}|null */
    private ?array $rendered = null;

    public function __construct(
        private readonly PortalCatalogue $catalogue,
        public readonly PortalPage $page,
    ) {}

    public function locale(): string
    {
        return $this->catalogue->locale;
    }

    public function lead(): ?string
    {
        return $this->rendered()['lead'];
    }

    /** @return list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}> */
    public function blocks(): array
    {
        return $this->rendered()['blocks'];
    }

    /** @return list<array{id: string, label: string}> */
    public function contents(): array
    {
        return $this->rendered()['contents'];
    }

    public function root(): ?PortalPage
    {
        return $this->catalogue->root();
    }

    /** @return list<array{index: PortalPage, pages: list<PortalPage>}> */
    public function sections(): array
    {
        return $this->catalogue->sections();
    }

    public function previous(): ?PortalPage
    {
        return $this->catalogue->previous($this->page);
    }

    public function next(): ?PortalPage
    {
        return $this->catalogue->next($this->page);
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        $items = [
            ['label' => __('Home'), 'url' => route('home.index')],
            ['label' => __('Documentation'), 'url' => route('docs.index')],
        ];

        if ($this->page->isRoot()) {
            return [...$items, ['label' => $this->page->title]];
        }

        $index = $this->catalogue->find($this->page->section);

        if ($index !== null && ! $this->page->isSectionIndex()) {
            $items[] = ['label' => $index->title, 'url' => $index->url()];
        }

        return [...$items, ['label' => $this->page->title]];
    }

    public function markdown(): string
    {
        $markdown = new PortalMarkdown($this->catalogue);

        return $markdown->resolveLinks(PortalMarkdown::body($this->page->contents()));
    }

    /** @return array{claude: string, chatgpt: string} */
    public function assistantLinks(): array
    {
        $prompt = rawurlencode("Read {$this->page->markdownUrl()} so I can ask you questions about it.");

        return [
            'claude' => "https://claude.ai/new?q={$prompt}",
            'chatgpt' => "https://chatgpt.com/?hints=search&q={$prompt}",
        ];
    }

    public function editUrl(): string
    {
        return self::SOURCE_URL."/edit/main/docs/portal/{$this->page->locale}/{$this->page->relativePath}";
    }

    public function issueUrl(): string
    {
        return self::SOURCE_URL.'/issues/new?title='.rawurlencode("Documentation: {$this->page->id}");
    }

    /** @return array{lead: ?string, blocks: list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>, contents: list<array{id: string, label: string}>} */
    private function rendered(): array
    {
        return $this->rendered ??= new PortalMarkdown($this->catalogue)->render(PortalMarkdown::body($this->page->contents()), $this->page->id);
    }
}
