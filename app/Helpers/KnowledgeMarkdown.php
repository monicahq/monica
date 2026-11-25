<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

/**
 * How the text of a Knowledge note, a list's description and a list's cells
 * are drawn. Raw HTML is escaped and unsafe links are refused, so whatever was
 * written is shown and never run. Links leaving the instance open in a new tab,
 * and an image is drawn as a link to its address.
 *
 * A task is drawn as a checkbox somebody can tick while reading. Each carries
 * its number in reading order, and taskLine() finds the line that number was
 * written on, so ticking it rewrites that line and never one inside a code
 * block.
 */
class KnowledgeMarkdown
{
    private static ?Environment $environment = null;

    public static function document(string $title, ?string $body): string
    {
        if (blank($body)) {
            return "# {$title}\n";
        }

        return "# {$title}\n\n{$body}";
    }

    public static function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $document = self::parse($markdown);
        $number = 0;

        foreach ($document->iterator() as $node) {
            if (! $node instanceof TaskListItemMarker) {
                continue;
            }

            $number++;
            $node->data->set('attributes/data-task', (string) $number);
            $node->data->set('attributes/aria-label', mb_trim(StringContainerHelper::getChildText($node->parent() ?? $node)));
        }

        return (string) new HtmlRenderer(self::environment())->renderDocument($document);
    }

    public static function inline(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        return mb_trim(Str::inlineMarkdown($markdown, self::options(), [new KnowledgeImageLinkExtension, new ExternalLinkExtension]));
    }

    /**
     * The headings of the text, from the first level to the third, with the
     * anchor each is drawn with.
     *
     * @return list<array{text: string, level: int, anchor: string}>
     */
    public static function headings(?string $markdown): array
    {
        if (blank($markdown)) {
            return [];
        }

        $headings = [];

        foreach (self::parse($markdown)->iterator() as $node) {
            if (! $node instanceof Heading || $node->getLevel() > 3) {
                continue;
            }

            $headings[] = [
                'text' => mb_trim(StringContainerHelper::getChildText($node)),
                'level' => $node->getLevel(),
                'anchor' => (string) $node->data->get('attributes/id', ''),
            ];
        }

        return $headings;
    }

    /**
     * The line, counted from one, the task of that number was written on, or
     * null when the text holds no such task.
     */
    public static function taskLine(?string $markdown, int $task): ?int
    {
        if (blank($markdown) || $task < 1) {
            return null;
        }

        $number = 0;

        foreach (self::parse($markdown)->iterator() as $node) {
            if (! $node instanceof TaskListItemMarker) {
                continue;
            }

            $number++;

            if ($number !== $task) {
                continue;
            }

            $item = $node->parent()?->parent();

            return $item instanceof ListItem ? $item->getStartLine() : null;
        }

        return null;
    }

    private static function parse(string $markdown): Document
    {
        return new MarkdownParser(self::environment())->parse($markdown);
    }

    private static function environment(): Environment
    {
        if (self::$environment instanceof Environment) {
            return self::$environment;
        }

        $environment = new Environment([
            ...self::options(),
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => 'section',
                'fragment_prefix' => 'section',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new KnowledgeImageLinkExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addRenderer(TaskListItemMarker::class, new KnowledgeTaskRenderer, 10);

        return self::$environment = $environment;
    }

    /** @return array<string, mixed> */
    private static function options(): array
    {
        return [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => (string) parse_url((string) config('app.url'), PHP_URL_HOST),
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ];
    }
}
