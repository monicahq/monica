<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'kind' => 'bug',
        'severity' => 'blocks_task',
        'title' => 'Yearly reminders on 29 February are refused',
        'tool' => 'create-contact-reminder',
        'steps' => [
            ['tool' => 'create-contact-reminder', 'arguments' => '{"day": 29, "month": 2}', 'outcome' => 'error', 'result' => 'The date field must be a valid date.'],
        ],
        'observed' => 'Any reminder on 29 February fails validation.',
        'expected' => 'The reminder is created.',
        'proposed_fix' => 'Validate against a leap year when no year is given.',
    ];

    #[Test]
    public function it_records_a_report(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/agent-feedback', $this->payload);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'status']);
        $response->assertJsonPath('status', 'new');
        $this->assertDatabaseHas('agent_feedback_reports', [
            'uuid' => $response->json('id'),
            'user_id' => $user->id,
            'title' => 'Yearly reminders on 29 February are refused',
        ]);
    }

    #[Test]
    public function it_refuses_a_report_without_what_was_expected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/agent-feedback', [...$this->payload, 'expected' => '']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('expected');
    }

    #[Test]
    public function it_refuses_a_placeholder_instead_of_a_description(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/agent-feedback', [...$this->payload, 'observed' => 'TODO.']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('observed');
    }
}
