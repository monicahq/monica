<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Docs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageActionsTest extends TestCase
{
    #[Test]
    public function it_announces_whether_the_page_was_copied_and_links_to_its_markdown(): void
    {
        $view = $this->blade('<x-docs.page-actions markdown="# Dunder Mifflin" markdown-url="/docs/en.md" />');

        $view->assertSee('x-data="docsPageActions(', false);
        $view->assertSee('aria-live="polite"', false);
        $view->assertSee('href="/docs/en.md"', false);
        $view->assertDontSee('Open in Claude');
    }

    #[Test]
    public function it_offers_to_open_the_page_in_an_assistant_when_given_the_links(): void
    {
        $view = $this->blade(
            '<x-docs.page-actions markdown="# Dunder Mifflin" markdown-url="/docs/en.md" :assistants="$assistants" />',
            ['assistants' => ['claude' => 'https://claude.ai/new?q=scranton', 'chatgpt' => 'https://chatgpt.com/?q=scranton']],
        );

        $view->assertSee('href="https://claude.ai/new?q=scranton"', false);
        $view->assertSee('href="https://chatgpt.com/?q=scranton"', false);
    }

    #[Test]
    public function it_hands_the_copied_markdown_to_the_function_the_page_names(): void
    {
        $this->blade('<x-docs.page-actions markdown="# Dunder Mifflin" markdown-url="/docs/mcp.md" transform="swap" />')
            ->assertSee('@click="copyPage(swap(markdown))"', false);
    }
}
