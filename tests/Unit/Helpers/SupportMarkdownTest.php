<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SupportMarkdown;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportMarkdownTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function hostileMessages(): array
    {
        return [
            'a script element' => ['Prison Mike was here <script>alert(document.cookie)</script>'],
            'an image with an error handler' => ['<img src=x onerror="alert(1)">'],
            'an svg with a load handler' => ['<svg onload="alert(1)"><circle /></svg>'],
            'a frame' => ['<iframe src="https://example.com/fake-sign-in"></iframe>'],
            'a style element' => ['<style>body { display: none }</style>'],
            'a script link' => ['[Dundies](javascript:alert(1))'],
            'a script link in mixed case' => ['[Dundies](JaVaScRiPt:alert(1))'],
            'a script link split by an encoded tab' => ['[Dundies](java&#x09;script:alert(1))'],
            'a data link' => ['[Dundies](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)'],
            'a data link to an image' => ['[Dundies](data:image/png;base64,iVBORw0KGgo=)'],
            'a link to a diagnostic tool' => ['[Dundies](ms-msdt:/id%20PCWDiagnostic)'],
            'a link to a file server' => ['[Dundies](ftp://example.com/payroll.xlsx)'],
            'an image of a diagnostic tool' => ['![Dundies](ms-msdt:/id%20PCWDiagnostic)'],
            'a script link in a reference' => ["[Dundies][ref]\n\n[ref]: javascript:alert(1)"],
            'a remote image' => ['![Scranton](https://example.com/pixel.gif)'],
            'a script image' => ['![Scranton](javascript:alert(1))'],
            'a fence whose language carries an attribute' => ["```js\" onclick=\"alert(1)\n\$x = 1;\n```"],
            'a raw link with a handler' => ['<a href="https://example.com" onmouseover="alert(1)">Toby</a>'],
            'an unclosed tag' => ['<div onclick="alert(1)" Michael'],
        ];
    }

    #[Test]
    #[DataProvider('hostileMessages')]
    public function nothing_in_a_message_can_run_or_load_anything(string $message): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml($message));

        $this->assertCount(0, $xpath->query('//script | //img | //iframe | //svg | //style | //object | //embed | //form'));

        foreach ($xpath->query('//*') as $element) {
            $this->assertInstanceOf(DOMElement::class, $element);

            foreach ($element->attributes as $attribute) {
                $this->assertDoesNotMatchRegularExpression('/^(on|style$|id$|src|x-|@|:)/i', $attribute->name, "{$element->tagName} carries {$attribute->name}");
            }

            if ($element->hasAttribute('href')) {
                $href = (string) preg_replace('/[\x00-\x20]/', '', $element->getAttribute('href'));

                if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $href, $scheme) === 1) {
                    $this->assertContains(mb_strtolower($scheme[1]), ['http', 'https', 'mailto']);
                }
            }
        }
    }

    #[Test]
    public function it_shows_raw_html_as_the_characters_written(): void
    {
        $html = SupportMarkdown::toHtml('Jim put <b>my stapler</b> in jello, a < b');

        $this->assertStringContainsString('&lt;b&gt;my stapler&lt;/b&gt;', $html);
        $this->assertStringContainsString('a &lt; b', $html);
    }

    #[Test]
    public function it_draws_an_image_as_a_link_to_its_address(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml('![Scranton](https://example.com/scranton.png)'));

        $this->assertSame('https://example.com/scranton.png', $xpath->query('//a')->item(0)?->getAttribute('href'));
        $this->assertSame('Scranton', $xpath->query('//a')->item(0)?->textContent);
    }

    #[Test]
    public function it_moves_headings_below_the_headings_of_the_screen(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml("# Steps\n\n## Details\n\n### More"));

        $this->assertCount(0, $xpath->query('//h1 | //h2 | //h3'));
        $this->assertSame('Steps', $xpath->query('//h4')->item(0)?->textContent);
        $this->assertSame('Details', $xpath->query('//h5')->item(0)?->textContent);
        $this->assertSame('More', $xpath->query('//h6')->item(0)?->textContent);
    }

    #[Test]
    public function the_highest_heading_of_a_message_is_always_the_fourth_level(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml("## Steps\n\n#### Details"));

        $this->assertSame('Steps', $xpath->query('//h4')->item(0)?->textContent);
        $this->assertSame('Details', $xpath->query('//h6')->item(0)?->textContent);
    }

    #[Test]
    public function it_keeps_a_single_line_break(): void
    {
        $this->assertStringContainsString('Dwight<br />', SupportMarkdown::toHtml("Dwight\nSchrute"));
    }

    #[Test]
    public function it_shows_a_link_it_refuses_as_its_words(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml('Call [Michael](tel:5705550100) or [Pam](mailto:pam@example.com)'));

        $this->assertSame(1, $xpath->query('//a')->length);
        $this->assertSame('mailto:pam@example.com', $xpath->query('//a')->item(0)?->getAttribute('href'));
        $this->assertStringContainsString('Call Michael or Pam', (string) $xpath->query('//p')->item(0)?->textContent);
    }

    #[Test]
    public function it_keeps_a_link_to_the_instance(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml('[the requests](/support/requests)'));

        $this->assertSame('/support/requests', $xpath->query('//a')->item(0)?->getAttribute('href'));
    }

    #[Test]
    public function it_shows_a_task_as_the_characters_written(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml("- [ ] Order paper\n- [x] Call Dunmore High"));

        $this->assertCount(0, $xpath->query('//input'));
        $this->assertSame('[ ] Order paper', $xpath->query('//li')->item(0)?->textContent);
        $this->assertSame('[x] Call Dunmore High', $xpath->query('//li')->item(1)?->textContent);
    }

    #[Test]
    public function it_draws_tables_strikethrough_and_bare_addresses(): void
    {
        $xpath = $this->parse(SupportMarkdown::toHtml("| Branch |\n| --- |\n| Scranton |\n\n~~Stamford~~ www.example.com"));

        $this->assertSame(1, $xpath->query('//table')->length);
        $this->assertSame('Stamford', $xpath->query('//del')->item(0)?->textContent);
        $this->assertSame('http://www.example.com', $xpath->query('//a')->item(0)?->getAttribute('href'));
    }

    #[Test]
    public function a_link_leaving_the_instance_opens_apart_and_tells_its_destination_nothing(): void
    {
        $link = $this->parse(SupportMarkdown::toHtml('[the guide](https://example.com/guide)'))->query('//a')->item(0);

        $this->assertSame('_blank', $link?->getAttribute('target'));
        $this->assertStringContainsString('noopener', (string) $link?->getAttribute('rel'));
        $this->assertStringContainsString('noreferrer', (string) $link?->getAttribute('rel'));
        $this->assertStringContainsString('nofollow', (string) $link?->getAttribute('rel'));
    }

    #[Test]
    public function it_draws_nothing_for_an_empty_message(): void
    {
        $this->assertSame('', SupportMarkdown::toHtml(''));
        $this->assertSame('', SupportMarkdown::toHtml(null));
    }

    #[Test]
    public function the_plain_text_is_the_words_without_their_marks(): void
    {
        $this->assertSame('Dwight said "bears" & beets', SupportMarkdown::plainText("**Dwight** _said_\n\n\"bears\" & beets"));
    }

    #[Test]
    public function a_message_built_to_be_slow_is_still_drawn_promptly(): void
    {
        foreach ([str_repeat('>', 5000).' Kevin', str_repeat('*', 10000), str_repeat('- ', 5000).'Oscar'] as $message) {
            $started = hrtime(true);

            SupportMarkdown::toHtml($message);

            $this->assertLessThan(1.0, (hrtime(true) - $started) / 1e9);
        }
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>');

        return new DOMXPath($document);
    }
}
