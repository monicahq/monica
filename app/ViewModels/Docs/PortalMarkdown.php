<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class PortalMarkdown
{
    private const string DOC_LINK = '/@doc\(\s*([a-z0-9._-]+)\s*(?:,\s*"([^"]*)")?\s*\)/';

    private const string SCREENSHOT = '/^::screenshot\{(.*)\}$/';

    private MarkdownConverter $converter;

    /** @var array<string, true> */
    private array $anchors = [];

    private string $pageId = '';

    /** @var list<array{id: string, label: string}> */
    private array $contents = [];

    public function __construct(
        private readonly PortalCatalogue $catalogue,
    ) {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public static function body(string $contents): string
    {
        return ltrim((string) preg_replace('/^---\n.*?\n---\n/s', '', $contents, 1));
    }

    /** @return list<string> */
    public static function references(string $body): array
    {
        preg_match_all(self::DOC_LINK, $body, $matches);

        return array_values(array_unique($matches[1]));
    }

    /** @return list<string> */
    public static function images(string $body): array
    {
        preg_match_all('/^::screenshot\{.*src="([^"]*)".*\}$/m', $body, $matches);

        return $matches[1];
    }

    public static function imageDirectory(string $locale, string $pageId): string
    {
        return public_path("images/docs/{$locale}/{$pageId}");
    }

    public function resolveLinks(string $body): string
    {
        return (string) preg_replace_callback(self::DOC_LINK, function (array $matches): string {
            $page = $this->catalogue->page($matches[1]);
            $label = ($matches[2] ?? '') !== '' ? $matches[2] : ($page->title ?? $matches[1]);

            if ($page === null) {
                return $label;
            }

            return "[{$label}]({$page->url()})";
        }, $body);
    }

    /**
     * @return array{
     *   lead: ?string,
     *   blocks: list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>,
     *   contents: list<array{id: string, label: string}>
     * }
     */
    public function render(string $body, string $pageId): array
    {
        $this->pageId = $pageId;
        $this->anchors = [];
        $this->contents = [];

        $body = (string) preg_replace('/^\s*#\s+[^\n]*\n/', '', $this->resolveLinks($body), 1);
        $lead = null;

        if (preg_match('/^\s*([^\n]+(?:\n[^\n]+)*)(?:\s*\n)?/', $body, $matches) && ! preg_match('/^[#>\-*|`:!\d]/', $matches[1])) {
            $lead = rtrim(Str::inlineMarkdown($matches[1], ['html_input' => 'escape']));
            $body = substr($body, strlen($matches[0]));
        }

        return [
            'lead' => $lead,
            'blocks' => $this->blocks(explode("\n", $body)),
            'contents' => $this->contents,
        ];
    }

    /**
     * @param  list<string>  $lines
     * @return list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>
     */
    private function blocks(array $lines): array
    {
        $blocks = [];
        $prose = [];
        $inCode = false;
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = rtrim($lines[$i]);

            if (preg_match('/^(```|~~~)/', ltrim($line))) {
                $inCode = ! $inCode;
                $prose[] = $line;

                continue;
            }

            if ($inCode) {
                $prose[] = $line;

                continue;
            }

            if (preg_match(self::SCREENSHOT, $line, $matches)) {
                $blocks = $this->flush($blocks, $prose);
                $prose = [];
                $blocks[] = ['type' => 'screenshot', 'screenshot' => $this->screenshot($matches[1])];

                continue;
            }

            if (! preg_match('/^(:{3,})(note|warning|steps)(.*)$/', $line, $matches)) {
                $prose[] = $line;

                continue;
            }

            $blocks = $this->flush($blocks, $prose);
            $prose = [];
            $inner = $this->consume($lines, $i, $matches[1]);

            $blocks[] = $matches[2] === 'steps'
                ? ['type' => 'steps', 'steps' => $this->steps($inner)]
                : [
                    'type' => $matches[2],
                    'title' => $this->attribute($matches[3], 'title'),
                    'html' => rtrim(Str::inlineMarkdown(trim(implode("\n", $inner)), ['html_input' => 'escape'])),
                ];
        }

        return $this->flush($blocks, $prose);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function consume(array $lines, int &$i, string $fence): array
    {
        $inner = [];
        $count = count($lines);

        for ($i++; $i < $count; $i++) {
            if (rtrim($lines[$i]) === $fence) {
                return $inner;
            }

            $inner[] = $lines[$i];
        }

        return $inner;
    }

    /**
     * @param  list<string>  $inner
     * @return list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>
     */
    private function steps(array $inner): array
    {
        $steps = [];
        $count = count($inner);

        for ($i = 0; $i < $count; $i++) {
            if (! preg_match('/^(:{3})step(.*)$/', rtrim($inner[$i]), $matches)) {
                continue;
            }

            $title = $this->attribute($matches[2], 'title');
            $screenshot = null;
            $text = [];

            foreach ($this->consume($inner, $i, $matches[1]) as $line) {
                if (preg_match(self::SCREENSHOT, rtrim($line), $found)) {
                    $screenshot = $this->screenshot($found[1]);

                    continue;
                }

                $text[] = $line;
            }

            $steps[] = [
                'title' => $title,
                'html' => $this->convert(implode("\n", $text)),
                'screenshot' => $screenshot,
            ];
        }

        return $steps;
    }

    /**
     * @param  list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>  $blocks
     * @param  list<string>  $prose
     * @return list<array{type: string, html?: string, title?: ?string, screenshot?: array{label: string, src: ?string}, steps?: list<array{title: ?string, html: string, screenshot: ?array{label: string, src: ?string}}>}>
     */
    private function flush(array $blocks, array $prose): array
    {
        $html = $this->convert(implode("\n", $prose));

        if ($html !== '') {
            $blocks[] = ['type' => 'prose', 'html' => $html];
        }

        return $blocks;
    }

    private function convert(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        $html = $this->converter->convert(trim($markdown))->getContent();

        return (string) preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $matches): string {
            $label = trim(html_entity_decode(strip_tags($matches[2])));
            $id = Str::slug($label) ?: 'section';
            $base = $id;
            $suffix = 2;

            while (isset($this->anchors[$id])) {
                $id = "{$base}-{$suffix}";
                $suffix++;
            }

            $this->anchors[$id] = true;

            if ($matches[1] === '3') {
                return "<h3 id=\"{$id}\">{$matches[2]}</h3>";
            }

            $this->contents[] = ['id' => $id, 'label' => $label];
            $name = e("Link to {$label}");

            return "<h2 id=\"{$id}\">{$matches[2]}<a href=\"#{$id}\" data-heading-anchor aria-label=\"{$name}\">#</a></h2>";
        }, $html);
    }

    /** @return array{label: string, src: ?string} */
    private function screenshot(string $attributes): array
    {
        $source = $this->attribute($attributes, 'src');

        return [
            'label' => $this->attribute($attributes, 'label') ?? '',
            'src' => $source === null ? null : asset("images/docs/{$this->catalogue->locale}/{$this->pageId}/{$source}"),
        ];
    }

    private function attribute(string $attributes, string $key): ?string
    {
        return preg_match('/'.$key.'="([^"]*)"/', $attributes, $matches) ? $matches[1] : null;
    }
}
