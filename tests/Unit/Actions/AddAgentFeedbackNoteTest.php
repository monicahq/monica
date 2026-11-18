<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddAgentFeedbackNote;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddAgentFeedbackNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_leaves_a_note_and_keeps_the_status(): void
    {
        Queue::fake();
        $administrator = User::factory()->create(['first_name' => 'Oscar', 'last_name' => 'Martinez']);
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $report = AgentFeedbackReport::factory()->create();

        $note = new AddAgentFeedbackNote(
            administrator: $administrator,
            report: $report,
            body: 'Confirmed on a test account.',
        )->execute();

        $this->assertDatabaseHas('agent_feedback_notes', [
            'id' => $note->id,
            'agent_feedback_report_id' => $report->id,
            'author_name' => 'Oscar Martinez',
            'body' => 'Confirmed on a test account.',
        ]);
        $this->assertSame('new', $report->refresh()->status->value);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::AgentFeedbackNoteAdded);
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new AddAgentFeedbackNote(
            administrator: User::factory()->create(),
            report: AgentFeedbackReport::factory()->create(),
            body: 'Dwight was here.',
        )->execute();
    }
}
