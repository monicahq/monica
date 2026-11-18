<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Admin;

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

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'number',
            'status',
            'kind',
            'severity',
            'title',
            'tool',
            'request',
            'steps',
            'observed',
            'expected',
            'proposed_fix',
            'proposed_code',
            'model',
            'connection_name',
            'account_id',
            'user',
            'submitted_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_reports_with_a_count_per_status(): void
    {
        AgentFeedbackReport::factory()->create();
        AgentFeedbackReport::factory()->dismissed()->create();

        $response = $this->withToken($this->administratorKey(['*', 'administration']))->json('GET', '/api/v1/admin/agent-feedback');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total', 'counts']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.counts.dismissed', 1);
    }

    #[Test]
    public function it_shows_a_report_with_its_notes(): void
    {
        $report = AgentFeedbackReport::factory()->create();

        $response = $this->withToken($this->administratorKey(['*', 'administration']))->json('GET', "/api/v1/admin/agent-feedback/{$report->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.notes', []);
    }

    #[Test]
    public function it_adds_a_note_and_moves_a_report(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->create();
        $key = $this->administratorKey(['*', 'administration']);

        $this->withToken($key)->json('POST', "/api/v1/admin/agent-feedback/{$report->uuid}/notes", ['body' => 'Planned for the next release.'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.notes.0.body', 'Planned for the next release.');

        $this->withToken($key)->json('PUT', "/api/v1/admin/agent-feedback/{$report->uuid}/status", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'accepted');
    }

    #[Test]
    public function it_refuses_a_move_the_status_does_not_allow(): void
    {
        $report = AgentFeedbackReport::factory()->create();

        $this->withToken($this->administratorKey(['*', 'administration']))->json('PUT', "/api/v1/admin/agent-feedback/{$report->uuid}/status", ['status' => 'fixed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    #[Test]
    public function it_is_closed_to_a_key_without_the_administration_scope(): void
    {
        $this->withToken($this->administratorKey(['*']))->json('GET', '/api/v1/admin/agent-feedback')->assertNotFound();
    }

    private function administratorKey(array $abilities): string
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael->createToken('Michael’s script', $abilities)->plainTextToken;
    }
}
