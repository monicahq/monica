<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\McpDocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpDocsControllerTest extends TestCase
{
    #[Test]
    public function it_shows_the_mcp_page_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get('/docs/mcp');

        $response->assertOk();
        $response->assertSee('Model Context Protocol (MCP)');
        $response->assertSee(new McpDocsViewModel()->serverAddress());
        $response->assertSee('role="tablist"', false);
    }
}
