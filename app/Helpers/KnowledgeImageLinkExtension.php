<?php

declare(strict_types=1);

namespace App\Helpers;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Draws an image written in a Knowledge note as a link to its address. An
 * image would make the reader's browser fetch an address somebody else chose
 * the moment the note opens, which says who read it and when. A link is
 * fetched only when it is followed, and goes through the same checks as any
 * other link.
 */
class KnowledgeImageLinkExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->replaceImages(...));
    }

    private function replaceImages(DocumentParsedEvent $event): void
    {
        $images = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Image) {
                $images[] = $node;
            }
        }

        foreach ($images as $image) {
            $link = new Link($image->getUrl(), $image->hasChildren() ? null : $image->getUrl(), $image->getTitle());

            foreach ($image->children() as $child) {
                $link->appendChild($child);
            }

            $image->replaceWith($link);
        }
    }
}
