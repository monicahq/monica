<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use App\ViewModels\Docs\ApiDocsSection;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodePanelTest extends TestCase
{
    private function section(): ApiDocsSection
    {
        return new ApiDocsSection([
            'id' => 'dundies-create',
            'title' => 'Hand out a Dundie',
            'method' => 'POST',
            'path' => '/dundies',
            'description' => 'Gives somebody an award nobody asked for.',
            'rateLimit' => 'Thirty requests per minute, per token.',
            'bodyParams' => [
                ['name' => 'recipient', 'type' => 'string', 'required' => true, 'description' => 'Who it goes to.', 'example' => 'Michael'],
            ],
            'response' => ['awarded' => true],
        ]);
    }

    #[Test]
    public function what_renders_is_exactly_the_sample_that_was_built(): void
    {
        $section = $this->section();
        $rendered = $this->lines($this->blade('<x-api-docs.code-panel :section="$section" />', ['section' => $section]));

        $expected = [];

        foreach ($section->samples() as $sample) {
            foreach (explode("\n", $sample['code']) as $line) {
                $expected[] = $line === '' ? ' ' : $line;
            }
        }

        $this->assertEquals(
            $expected,
            $rendered,
            'The box preserves whitespace, so a newline or an indent between the spans is printed as part of the sample.',
        );
    }

    #[Test]
    public function the_response_renders_exactly_the_body_that_was_built(): void
    {
        $section = $this->section();
        $rendered = $this->lines($this->blade('<x-api-docs.response-panel :section="$section" />', ['section' => $section]));

        $this->assertEquals(['{', '    "awarded": true', '}'], $rendered);
    }

    #[Test]
    public function it_offers_every_language_and_a_way_to_copy(): void
    {
        $panel = $this->blade('<x-api-docs.code-panel :section="$section" />', ['section' => $this->section()]);

        $panel->assertSee('cURL');
        $panel->assertSee('JavaScript');
        $panel->assertSee('PHP');
        $panel->assertSee('Copy');
        $panel->assertSee('aria-pressed', escape: false);
    }

    /** @return list<string> */
    private function lines(TestView $panel): array
    {
        preg_match_all('/<div class="font-mono[^"]*">(.*?)<\/div>/s', (string) $panel, $matches);

        return array_map(
            fn (string $line): string => html_entity_decode(strip_tags($line), ENT_QUOTES),
            $matches[1],
        );
    }
}
