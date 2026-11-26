<?php

declare(strict_types=1);

namespace App\Helpers;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * How the text of a support message is drawn. The settings that make it safe
 * live here and nowhere else, because the framework's defaults let raw HTML
 * and script links through: raw HTML is escaped, a link is active only for a
 * web page or an email address, an image is drawn as a link to its address,
 * no form control is drawn, and the cost of formatting is bounded whatever
 * the text.
 */
class SupportMarkdown
{
    public static function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'max_delimiters_per_line' => 100,
            'renderer' => [
                'soft_break' => "<br />\n",
            ],
            'external_link' => [
                'internal_hosts' => (string) parse_url((string) config('app.url'), PHP_URL_HOST),
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
                'nofollow' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new KnowledgeImageLinkExtension);
        $environment->addExtension(new SupportLinkSchemeExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new SupportHeadingLevelExtension);

        return (string) new MarkdownConverter($environment)->convert($markdown);
    }

    public static function plainText(?string $markdown): string
    {
        $text = html_entity_decode(strip_tags(self::toHtml($markdown)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
