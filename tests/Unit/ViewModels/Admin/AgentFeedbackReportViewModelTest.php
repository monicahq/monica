<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\AgentFeedbackReport;
use App\Models\User;
use App\ViewModels\Admin\AgentFeedbackReportViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackReportViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_other_reports_about_the_same_tool_newest_first(): void
    {
        $report = AgentFeedbackReport::factory()->create(['tool' => 'search-records']);
        $older = AgentFeedbackReport::factory()->create(['tool' => 'search-records', 'title' => 'Accents', 'created_at' => now()->subWeek()]);
        $newer = AgentFeedbackReport::factory()->fixed()->create(['tool' => 'search-records', 'title' => 'Nicknames', 'created_at' => now()->subDay()]);
        AgentFeedbackReport::factory()->create(['tool' => 'list-contacts']);

        $sameTool = new AgentFeedbackReportViewModel(administrator: User::factory()->create(), report: $report)->sameTool();

        $this->assertSame(['Nicknames', 'Accents'], array_column($sameTool, 'title'));
        $this->assertStringStartsWith('Fixed · ', $sameTool[0]['meta']);
    }

    #[Test]
    public function it_offers_only_the_moves_the_status_allows(): void
    {
        $administrator = User::factory()->create();

        $this->assertSame(['Accept', 'Dismiss'], array_column(new AgentFeedbackReportViewModel(administrator: $administrator, report: AgentFeedbackReport::factory()->create())->transitions(), 'label'));
        $this->assertSame(['Reopen'], array_column(new AgentFeedbackReportViewModel(administrator: $administrator, report: AgentFeedbackReport::factory()->dismissed()->create())->transitions(), 'label'));
    }

    #[Test]
    public function it_writes_the_report_as_markdown_for_an_issue_tracker(): void
    {
        $report = AgentFeedbackReport::factory()->create([
            'title' => 'Long notes are cut',
            'tool' => 'get-contact-markdown',
            'observed' => 'The note ends after 4,000 characters.',
            'expected' => 'The whole note.',
            'proposed_fix' => 'Remove the limit.',
            'proposed_code' => '$body = $note->body;',
        ]);

        $markdown = new AgentFeedbackReportViewModel(administrator: User::factory()->create(), report: $report)->markdown();

        $this->assertSame("## Long notes are cut\n\nTool: `get-contact-markdown`\n\n### Observed\nThe note ends after 4,000 characters.\n\n### Expected\nThe whole note.\n\n### Proposed fix\nRemove the limit.\n\n```\n\$body = \$note->body;\n```", $markdown);
    }

    #[Test]
    public function it_numbers_the_steps_the_agent_tried(): void
    {
        $report = AgentFeedbackReport::factory()->create(['steps' => [
            ['tool' => 'search-records', 'arguments' => null, 'outcome' => 'success', 'result' => 'Nothing found.'],
            ['tool' => 'search-records', 'arguments' => null, 'outcome' => 'error', 'result' => null],
        ]]);

        $steps = new AgentFeedbackReportViewModel(administrator: User::factory()->create(), report: $report)->steps();

        $this->assertSame([1, 2], array_column($steps, 'number'));
        $this->assertSame([true, false], array_column($steps, 'succeeded'));
    }
}
