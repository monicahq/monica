<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpDocsMarkdownControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_serves_the_mcp_page_as_markdown_to_anybody(): void
    {
        $response = $this->get('/docs/mcp.md');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->assertStringStartsWith('# Model Context Protocol (MCP)', $response->getContent());
    }
}
