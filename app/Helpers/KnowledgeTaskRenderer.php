<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Stringable;

/**
 * Draws a task of a Knowledge note as a checkbox somebody can tick, where the
 * Markdown library draws a disabled one. Its number and its label are set by
 * {@see KnowledgeMarkdown::toHtml()}.
 */
class KnowledgeTaskRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): Stringable
    {
        if (! $node instanceof TaskListItemMarker) {
            throw new InvalidArgumentException('Only a task marker can be drawn as a task checkbox.');
        }

        $checkbox = new HtmlElement('input', [], '', true);

        foreach (['data-task', 'aria-label'] as $attribute) {
            $value = $node->data->get("attributes/{$attribute}", null);

            if (is_string($value)) {
                $checkbox->setAttribute($attribute, $value);
            }
        }

        $checkbox->setAttribute('type', 'checkbox');

        if ($node->isChecked()) {
            $checkbox->setAttribute('checked', '');
        }

        return $checkbox;
    }
}
