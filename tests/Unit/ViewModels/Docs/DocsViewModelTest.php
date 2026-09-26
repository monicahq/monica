<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\ApiDocsViewModel;
use App\ViewModels\Docs\DocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocsViewModelTest extends TestCase
{
    #[Test]
    public function it_links_only_the_areas_that_have_pages(): void
    {
        $urls = array_column(new DocsViewModel()->areas(), 'url', 'title');

        $this->assertSame([
            'Using Monica' => null,
            'API' => route('docs.api.index'),
            'MCP' => route('docs.mcp.index'),
            'CardDAV and CalDAV' => null,
        ], $urls);
    }

    #[Test]
    public function it_lists_every_page_of_the_reference_in_the_text_file_for_assistants(): void
    {
        $text = new DocsViewModel()->llmsText();

        $this->assertStringContainsString('('.route('docs.api.markdown.index').')', $text);
        $this->assertStringContainsString('- [MCP]('.route('docs.mcp.markdown.index').')', $text);

        foreach (new ApiDocsViewModel()->sections() as $section) {
            $this->assertStringContainsString(
                "- [{$section->title}](".route('docs.api.markdown.show', ['section' => $section->id]).')',
                $text,
            );
        }
    }

    #[Test]
    public function it_writes_the_text_file_for_assistants_in_english_whatever_the_locale(): void
    {
        app()->setLocale('fr_FR');

        $text = new DocsViewModel()->llmsText();

        $this->assertStringStartsWith("# Monica\n", $text);
        $this->assertStringContainsString('## API reference', $text);
    }
}
