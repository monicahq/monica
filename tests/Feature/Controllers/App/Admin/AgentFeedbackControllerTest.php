<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_new_reports(): void
    {
        AgentFeedbackReport::factory()->create(['title' => 'Search ignores accents in one direction']);

        $this->actingAs($this->administrator())->get(route('admin.agentFeedback.index'))
            ->assertStatus(200)
            ->assertSee('Agent reports')
            ->assertSee('Search ignores accents in one direction');
    }

    #[Test]
    public function it_opens_a_report(): void
    {
        $report = AgentFeedbackReport::factory()->create(['proposed_fix' => 'Normalise both sides before matching.']);

        $this->actingAs($this->administrator())->get(route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid]))
            ->assertStatus(200)
            ->assertSee('Normalise both sides before matching.')
            ->assertSee(route('admin.agentFeedback.status.update', ['agentFeedbackReport' => $report->uuid]));
    }

    #[Test]
    public function it_moves_a_report(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->create();

        $this->actingAs($this->administrator())->put(route('admin.agentFeedback.status.update', ['agentFeedbackReport' => $report->uuid]), [
            'status' => 'accepted',
        ])
            ->assertRedirect(route('admin.agentFeedback.show', ['agentFeedbackReport' => $report->uuid]))
            ->assertSessionHas('status', 'Status changed.');

        $this->assertEquals('accepted', $report->refresh()->status->value);
    }

    #[Test]
    public function it_refuses_a_move_the_status_does_not_allow(): void
    {
        $report = AgentFeedbackReport::factory()->create();

        $this->actingAs($this->administrator())->put(route('admin.agentFeedback.status.update', ['agentFeedbackReport' => $report->uuid]), [
            'status' => 'fixed',
        ])->assertSessionHasErrors(['status']);
    }

    #[Test]
    public function it_adds_a_note(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->create();

        $this->actingAs($this->administrator())->post(route('admin.agentFeedback.notes.create', ['agentFeedbackReport' => $report->uuid]), [
            'body' => 'Confirmed on a test account.',
        ])->assertSessionHas('status', 'Note added.');

        $this->assertDatabaseHas('agent_feedback_notes', ['agent_feedback_report_id' => $report->id, 'body' => 'Confirmed on a test account.']);
    }

    #[Test]
    public function it_refuses_an_empty_note(): void
    {
        $report = AgentFeedbackReport::factory()->create();

        $this->actingAs($this->administrator())->post(route('admin.agentFeedback.notes.create', ['agentFeedbackReport' => $report->uuid]), [
            'body' => '',
        ])->assertSessionHasErrors(['body']);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
