<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\AgentFeedbackQueue;
use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use App\ViewModels\Admin\AgentFeedbackQueueViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackQueueViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_describes_each_report_with_its_number_its_person_and_its_notes(): void
    {
        $jim = User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);
        $report = AgentFeedbackReport::factory()->create(['user_id' => $jim->id, 'account_id' => $jim->account_id, 'model' => 'Claude Sonnet']);
        AgentFeedbackNote::factory()->count(2)->create(['agent_feedback_report_id' => $report->id]);

        $row = new AgentFeedbackQueueViewModel(queue: new AgentFeedbackQueue)->rows()[0];

        $this->assertStringStartsWith("#{$report->id} · ", $row['meta']);
        $this->assertStringEndsWith('Claude Sonnet · Jim Halpert', $row['meta']);
        $this->assertSame(2, $row['noteCount']);
        $this->assertSame('Needs a workaround', $row['severityLabel']);
    }

    #[Test]
    public function it_says_why_the_list_is_empty(): void
    {
        $this->assertSame('Nothing new', new AgentFeedbackQueueViewModel(queue: new AgentFeedbackQueue)->emptyTitle());
        $this->assertSame('Nothing dismissed', new AgentFeedbackQueueViewModel(queue: new AgentFeedbackQueue(status: 'dismissed'))->emptyTitle());
        $this->assertSame('No report matches these filters', new AgentFeedbackQueueViewModel(queue: new AgentFeedbackQueue(search: 'Scranton'))->emptyTitle());
    }
}
