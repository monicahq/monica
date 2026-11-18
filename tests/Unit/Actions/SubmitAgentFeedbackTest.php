<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SubmitAgentFeedback;
use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitAgentFeedbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_new_report_for_the_administrators(): void
    {
        Queue::fake();
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $report = new SubmitAgentFeedback(
            user: $user,
            connectionName: 'Claude Desktop',
            kind: AgentFeedbackKindEnum::MissingTool,
            severity: AgentFeedbackSeverityEnum::BlocksTask,
            title: '  No way to list contacts by birthday month ',
            observed: 'The agent had to open each contact.',
            expected: 'One call returns the contacts born in a month.',
            proposedFix: 'Add a birthday_month filter to list-contacts.',
            tool: 'list-contacts',
            request: 'Who has a birthday in December?',
            steps: [['tool' => 'list-contacts', 'outcome' => 'success']],
            proposedCode: '$query->whereMonth(...)',
            model: 'Claude Opus',
        )->execute();

        $this->assertInstanceOf(AgentFeedbackReport::class, $report);
        $this->assertDatabaseHas('agent_feedback_reports', [
            'id' => $report->id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'status' => 'new',
            'kind' => 'missing_tool',
            'severity' => 'blocks_task',
            'title' => 'No way to list contacts by birthday month',
            'tool' => 'list-contacts',
            'model' => 'Claude Opus',
            'connection_name' => 'Claude Desktop',
        ]);
        $this->assertEquals([['tool' => 'list-contacts', 'arguments' => null, 'outcome' => 'success', 'result' => null]], $report->refresh()->steps);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::AgentFeedbackSubmitted
            && $job->parameters === ['title' => 'No way to list contacts by birthday month']);
    }

    #[Test]
    public function it_goes_with_the_account(): void
    {
        $report = AgentFeedbackReport::factory()->create();

        $report->account->delete();

        $this->assertModelMissing($report);
    }
}
