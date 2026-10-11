<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\Mcp\Servers\EricaServer;
use App\ViewModels\Docs\McpDocsViewModel;
use Laravel\Mcp\Server\Tool;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class McpDocsViewModelTest extends TestCase
{
    #[Test]
    public function it_builds_the_server_address_from_the_instance_address(): void
    {
        config(['app.url' => 'https://monica.test/crm/']);

        $this->assertSame('https://monica.test/crm/mcp', new McpDocsViewModel()->serverAddress());
    }

    #[Test]
    public function it_offers_every_client_in_the_order_of_the_design(): void
    {
        $viewModel = new McpDocsViewModel;
        $clients = $viewModel->clients();

        $this->assertSame(
            ['Claude Code', 'Cursor', 'Codex', 'VS Code', 'Claude Desktop', 'ChatGPT', 'Other'],
            array_column($clients, 'label'),
        );

        foreach ($clients as $client) {
            $blocks = array_filter(array_column($client['content'], 'block'));

            $this->assertNotSame([], $blocks);

            foreach ($blocks as $block) {
                $this->assertStringContainsString($viewModel->serverAddress(), $block['code']);
            }
        }
    }

    #[Test]
    public function it_lists_exactly_the_tools_the_server_registers(): void
    {
        /** @var list<class-string<Tool>> $classes */
        $classes = new ReflectionClass(EricaServer::class)->getProperty('tools')->getDefaultValue();
        $registered = array_map(fn (string $class): string => app($class)->name(), $classes);

        $documented = [];

        foreach (new McpDocsViewModel()->toolGroups() as $group) {
            array_push($documented, ...array_column($group['tools'], 'name'));
        }

        sort($registered);
        sort($documented);

        $this->assertSame($registered, $documented);
    }

    #[Test]
    public function it_counts_the_tools_it_lists(): void
    {
        $this->assertStringStartsWith('MCP currently exposes 87 tools.', new McpDocsViewModel()->toolsSummary()[0]);
    }

    #[Test]
    public function it_marks_only_the_groups_that_change_data(): void
    {
        $changing = array_filter(new McpDocsViewModel()->toolGroups(), fn (array $group): bool => $group['changesData']);

        $this->assertSame(
            ['Contacts, changing', 'Reminders, changing', 'Life events, changing', 'Templates, changing', 'Template drafts', 'Sections', 'Fields', 'Entity types, changing', 'Relationship vocabulary, changing', 'Life event vocabulary, changing'],
            array_values(array_column($changing, 'name')),
        );
    }

    #[Test]
    public function it_explains_how_to_connect_and_disconnect_an_assistant_through_oauth(): void
    {
        $this->assertStringContainsString('OAuth', implode(' ', McpDocsViewModel::SETUP));
        $this->assertStringContainsString('Connected assistants', implode(' ', McpDocsViewModel::ACCESS));
    }

    #[Test]
    public function it_gives_the_hosted_request_limit(): void
    {
        config(['monica.self_hosted' => false]);

        $limit = array_column(new McpDocsViewModel()->troubleshooting(), 'text', 'title')['Too many requests'];

        $this->assertStringContainsString('60 requests a minute for its key', $limit);
    }

    #[Test]
    public function it_says_a_self_hosted_instance_sets_no_limit(): void
    {
        config(['monica.self_hosted' => true]);

        $limit = array_column(new McpDocsViewModel()->troubleshooting(), 'text', 'title')['Too many requests'];

        $this->assertStringContainsString('This instance sets no limit on MCP requests', $limit);
    }

    #[Test]
    public function it_writes_every_section_and_configuration_in_the_markdown(): void
    {
        $viewModel = new McpDocsViewModel;
        $markdown = $viewModel->markdown();

        $this->assertStringStartsWith("# Model Context Protocol (MCP)\n", $markdown);

        foreach (array_slice(McpDocsViewModel::SECTIONS, 1) as $section) {
            $this->assertStringContainsString("## {$section['label']}\n", $markdown);
        }

        foreach ($viewModel->clients() as $client) {
            foreach (array_filter(array_column($client['content'], 'block')) as $block) {
                $this->assertStringContainsString($block['code'], $markdown);
            }
        }

        $this->assertStringContainsString("1. `list-templates`, to find the template.\n", $markdown);
    }

    #[Test]
    public function it_points_both_assistant_links_at_the_markdown(): void
    {
        $address = rawurlencode(route('docs.mcp.markdown.index'));

        foreach (new McpDocsViewModel()->assistantLinks() as $link) {
            $this->assertStringContainsString($address, $link);
        }
    }
}
