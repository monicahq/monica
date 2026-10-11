<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\McpDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpDocsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_mcp_page_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get('/docs/mcp');

        $response->assertOk();
        $response->assertSee('Model Context Protocol (MCP)');
        $response->assertSee(new McpDocsViewModel()->serverAddress());
        $response->assertSee('role="tablist"', false);
    }

    #[Test]
    public function it_answers_a_visitor_only_while_the_flag_is_on_for_every_account(): void
    {
        Feature::for(null)->deactivate('mcp-server');
        Feature::flushCache();

        $this->get('/docs/mcp')->assertNotFound();
        $this->get('/docs/mcp.md')->assertNotFound();

        Feature::for(null)->activate('mcp-server');
        Feature::flushCache();

        $this->get('/docs/mcp')->assertOk();
    }
}
