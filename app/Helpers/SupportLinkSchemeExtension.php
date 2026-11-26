<?php

declare(strict_types=1);

namespace App\Helpers;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Keeps a link in a support message active only when it leads to a web page
 * or an email address, or stays on the instance. Any other scheme opens a
 * program on the reader's computer, so the link is replaced by its words.
 */
class SupportLinkSchemeExtension implements ExtensionInterface
{
    private const array ALLOWED_SCHEMES = ['http', 'https', 'mailto'];

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->deactivateLinks(...));
    }

    private function deactivateLinks(DocumentParsedEvent $event): void
    {
        $refusedLinks = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Link && ! $this->isAllowed($node->getUrl())) {
                $refusedLinks[] = $node;
            }
        }

        foreach ($refusedLinks as $link) {
            foreach ($link->children() as $child) {
                $link->insertBefore($child);
            }

            $link->detach();
        }
    }

    private function isAllowed(string $url): bool
    {
        $url = (string) preg_replace('/[\x00-\x20]/', '', $url);

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $scheme) !== 1) {
            return true;
        }

        return in_array(mb_strtolower($scheme[1]), self::ALLOWED_SCHEMES, true);
    }
}
