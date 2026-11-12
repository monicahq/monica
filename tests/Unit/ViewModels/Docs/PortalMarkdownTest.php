<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalMarkdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalMarkdownTest extends TestCase
{
    #[Test]
    public function it_takes_the_first_paragraph_after_the_title_as_the_lead(): void
    {
        $rendered = $this->render("# Dunder Mifflin\n\nThe **paper** company.\n\nIn Scranton.");

        $this->assertSame('The <strong>paper</strong> company.', $rendered['lead']);
        $this->assertSame([['type' => 'prose', 'html' => "<p>In Scranton.</p>\n"]], $rendered['blocks']);
    }

    #[Test]
    public function it_leaves_the_lead_empty_when_the_page_opens_on_something_other_than_a_paragraph(): void
    {
        $rendered = $this->render("# Dunder Mifflin\n\n- Michael\n- Dwight");

        $this->assertNull($rendered['lead']);
    }

    #[Test]
    public function it_resolves_a_link_to_another_page_with_its_title_or_the_label_given(): void
    {
        $markdown = $this->markdown();

        $this->assertSame(
            '[sign up]('.route('docs.portal.show', ['locale' => 'en', 'section' => 'start-here', 'slug' => 'create-your-account']).')',
            $markdown->resolveLinks('@doc(start.create-account, "sign up")'),
        );
    }

    #[Test]
    public function it_prints_the_label_alone_when_the_page_linked_to_does_not_exist(): void
    {
        $this->assertSame('the warehouse', $this->markdown()->resolveLinks('@doc(warehouse.tour, "the warehouse")'));
    }

    #[Test]
    public function it_turns_a_note_and_a_warning_into_their_own_blocks(): void
    {
        $rendered = $this->render("# Title\n\n:::note\nJim sits next to **Dwight**.\n:::\n\n:::warning title=\"Careful\"\nDwight keeps a knife.\n:::");

        $this->assertSame([
            ['type' => 'note', 'title' => null, 'html' => 'Jim sits next to <strong>Dwight</strong>.'],
            ['type' => 'warning', 'title' => 'Careful', 'html' => 'Dwight keeps a knife.'],
        ], $rendered['blocks']);
    }

    #[Test]
    public function it_turns_steps_into_numbered_steps_with_their_titles_and_screenshots(): void
    {
        $body = <<<'MARKDOWN'
            # Title

            ::::steps
            :::step title="Open the drawer"
            Open the **top** drawer.

            ::screenshot{label="The drawer"}
            :::

            :::step title="Take the stapler"
            Take it.
            :::
            ::::
            MARKDOWN;

        $rendered = $this->render($body);

        $this->assertSame([[
            'type' => 'steps',
            'steps' => [
                ['title' => 'Open the drawer', 'html' => "<p>Open the <strong>top</strong> drawer.</p>\n", 'screenshot' => ['label' => 'The drawer', 'src' => null]],
                ['title' => 'Take the stapler', 'html' => "<p>Take it.</p>\n", 'screenshot' => null],
            ],
        ]], $rendered['blocks']);
    }

    #[Test]
    public function it_looks_for_a_screenshot_in_the_folder_named_after_the_page(): void
    {
        $rendered = $this->render("# Title\n\n::screenshot{label=\"The annex\" src=\"vaults.show.webp\"}");

        $this->assertSame([[
            'type' => 'screenshot',
            'screenshot' => ['label' => 'The annex', 'src' => asset('images/docs/en/office.tour/vaults.show.webp')],
        ]], $rendered['blocks']);
    }

    #[Test]
    public function it_anchors_every_heading_and_lists_the_second_level_ones_as_the_contents(): void
    {
        $rendered = $this->render("# Title\n\n## The office\n\n### Upstairs\n\n## The office");

        $this->assertStringContainsString('<h2 id="the-office">The office<a href="#the-office" data-heading-anchor', $rendered['blocks'][0]['html']);
        $this->assertStringContainsString('<h3 id="upstairs">Upstairs</h3>', $rendered['blocks'][0]['html']);
        $this->assertSame([
            ['id' => 'the-office', 'label' => 'The office'],
            ['id' => 'the-office-2', 'label' => 'The office'],
        ], $rendered['contents']);
    }

    #[Test]
    public function it_leaves_a_directive_written_inside_a_code_block_alone(): void
    {
        $rendered = $this->render("# Title\n\n```\n:::note\n```");

        $this->assertSame('prose', $rendered['blocks'][0]['type']);
        $this->assertStringContainsString(':::note', $rendered['blocks'][0]['html']);
    }

    #[Test]
    public function it_escapes_html_written_in_a_page(): void
    {
        $rendered = $this->render("# Title\n\nFirst.\n\n<script>alert(1)</script>");

        $this->assertStringNotContainsString('<script>', $rendered['blocks'][0]['html']);
    }

    /** @return array{lead: ?string, blocks: list<array<string, mixed>>, contents: list<array{id: string, label: string}>} */
    private function render(string $body): array
    {
        return $this->markdown()->render($body, 'office.tour');
    }

    private function markdown(): PortalMarkdown
    {
        return new PortalMarkdown(catalogue: new PortalCatalogue(locale: 'en'));
    }
}
