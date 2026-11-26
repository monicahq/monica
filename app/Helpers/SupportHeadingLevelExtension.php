<?php

declare(strict_types=1);

namespace App\Helpers;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Moves the headings written in a support message below the screen's own.
 * The author's name is a third level heading, so the highest heading of the
 * text becomes a fourth level one and the others keep their distance from it,
 * capped at the sixth.
 */
class SupportHeadingLevelExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->lowerHeadings(...));
    }

    private function lowerHeadings(DocumentParsedEvent $event): void
    {
        $headings = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Heading) {
                $headings[] = $node;
            }
        }

        if ($headings === []) {
            return;
        }

        $shift = 4 - min(array_map(fn (Heading $heading): int => $heading->getLevel(), $headings));

        foreach ($headings as $heading) {
            $heading->setLevel(min(6, $heading->getLevel() + $shift));
        }
    }
}
