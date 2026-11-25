<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\KnowledgeMarkdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeMarkdownTest extends TestCase
{
    #[Test]
    public function a_document_is_the_title_as_a_heading_then_the_text_exactly_as_written(): void
    {
        $body = "Rules of the office:\n\n1. No Prison Mike\n   - Ever\n\n| Name | Dundies |\n| --- | --- |\n| Pam | 3 |\n\n- [ ] Order paper\n";

        $this->assertSame("# Dunder Mifflin\n\n{$body}", KnowledgeMarkdown::document('Dunder Mifflin', $body));
    }

    #[Test]
    public function a_document_without_text_is_the_title_alone(): void
    {
        $this->assertSame("# Dunder Mifflin\n", KnowledgeMarkdown::document('Dunder Mifflin', null));
        $this->assertSame("# Dunder Mifflin\n", KnowledgeMarkdown::document('Dunder Mifflin', ''));
    }

    #[Test]
    public function it_shows_raw_html_as_text(): void
    {
        $html = KnowledgeMarkdown::toHtml('Prison Mike says <script>alert(1)</script>');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    #[Test]
    public function it_refuses_an_unsafe_link(): void
    {
        $html = KnowledgeMarkdown::toHtml('[Click](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    #[Test]
    public function an_image_is_drawn_as_a_link_to_its_address(): void
    {
        $markdown = '![Dundie](https://dundermifflin.com/dundie.png)';

        $this->assertStringNotContainsString('<img', KnowledgeMarkdown::toHtml($markdown));
        $this->assertStringContainsString('href="https://dundermifflin.com/dundie.png"', KnowledgeMarkdown::toHtml($markdown));
        $this->assertStringNotContainsString('<img', KnowledgeMarkdown::inline($markdown));
        $this->assertStringContainsString('href="https://dundermifflin.com/dundie.png"', KnowledgeMarkdown::inline($markdown));
    }

    #[Test]
    public function a_bare_web_address_becomes_a_link_opening_in_a_new_tab(): void
    {
        $html = KnowledgeMarkdown::toHtml('Order at https://dundermifflin.com today');

        $this->assertStringContainsString('href="https://dundermifflin.com"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    #[Test]
    public function it_numbers_the_tasks_in_reading_order_and_lets_them_be_ticked(): void
    {
        $html = KnowledgeMarkdown::toHtml("- [x] Book the Dundies\n- [ ] Buy the trophies");

        $this->assertStringContainsString('<input data-task="1" aria-label="Book the Dundies" type="checkbox" checked="">', $html);
        $this->assertStringContainsString('<input data-task="2" aria-label="Buy the trophies" type="checkbox">', $html);
        $this->assertStringNotContainsString('disabled', $html);
    }

    #[Test]
    public function it_gives_each_heading_an_anchor(): void
    {
        $this->assertStringContainsString('<h2 id="section-bookings">Bookings</h2>', KnowledgeMarkdown::toHtml('## Bookings'));
    }

    #[Test]
    public function it_lists_the_headings_down_to_the_third_level(): void
    {
        $headings = KnowledgeMarkdown::headings("# Plan\n## Bookings\n### Hotels\n#### Rooms");

        $this->assertEquals([
            ['text' => 'Plan', 'level' => 1, 'anchor' => 'section-plan'],
            ['text' => 'Bookings', 'level' => 2, 'anchor' => 'section-bookings'],
            ['text' => 'Hotels', 'level' => 3, 'anchor' => 'section-hotels'],
        ], $headings);
    }

    #[Test]
    public function it_finds_the_line_a_task_was_written_on(): void
    {
        $markdown = "Trip to Nashua\n\n- [x] Flights\n- [ ] Hotel";

        $this->assertEquals(4, KnowledgeMarkdown::taskLine($markdown, 2));
        $this->assertNull(KnowledgeMarkdown::taskLine($markdown, 3));
    }

    #[Test]
    public function a_task_inside_a_code_block_is_not_a_task(): void
    {
        $markdown = "```\n- [ ] not a task\n```\n\n- [ ] Call Jan";

        $this->assertEquals(5, KnowledgeMarkdown::taskLine($markdown, 1));
    }

    #[Test]
    public function inline_text_reads_bold_and_links_and_escapes_html(): void
    {
        $html = KnowledgeMarkdown::inline('**Cooper\'s** <b>Seafood</b>');

        $this->assertEquals('<strong>Cooper\'s</strong> &lt;b&gt;Seafood&lt;/b&gt;', $html);
    }

    #[Test]
    public function nothing_written_draws_nothing(): void
    {
        $this->assertEquals('', KnowledgeMarkdown::toHtml(null));
        $this->assertEquals('', KnowledgeMarkdown::inline(''));
        $this->assertEquals([], KnowledgeMarkdown::headings(null));
    }
}
