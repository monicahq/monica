<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ChangeAgentFeedbackStatus;
use App\Enums\AdministratorActionEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeAgentFeedbackStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_report_and_writes_it_down(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->accepted()->create();

        new ChangeAgentFeedbackStatus(
            administrator: $this->administrator(),
            report: $report,
            status: AgentFeedbackStatusEnum::Fixed,
        )->execute();

        $this->assertDatabaseHas('agent_feedback_reports', ['id' => $report->id, 'status' => 'fixed']);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::AgentFeedbackStatusChanged
            && $job->parameters === ['reference' => "#{$report->id}", 'status' => 'Fixed']);
    }

    #[Test]
    public function it_refuses_a_move_the_status_does_not_allow(): void
    {
        $this->expectException(ValidationException::class);

        new ChangeAgentFeedbackStatus(
            administrator: $this->administrator(),
            report: AgentFeedbackReport::factory()->create(),
            status: AgentFeedbackStatusEnum::Fixed,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_move_decided_on_a_status_another_administrator_already_changed(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->accepted()->create();
        AgentFeedbackReport::query()->whereKey($report->id)->update(['status' => AgentFeedbackStatusEnum::Fixed->value]);

        try {
            new ChangeAgentFeedbackStatus(
                administrator: $this->administrator(),
                report: $report,
                status: AgentFeedbackStatusEnum::Dismissed,
            )->execute();

            $this->fail('The stale move was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('agent_feedback_reports', ['id' => $report->id, 'status' => 'fixed']);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ChangeAgentFeedbackStatus(
            administrator: User::factory()->create(),
            report: AgentFeedbackReport::factory()->create(),
            status: AgentFeedbackStatusEnum::Accepted,
        )->execute();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
