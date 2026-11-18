<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\AddAgentFeedbackNote;
use App\Mcp\Tools\ChangeAgentFeedbackStatus;
use App\Mcp\Tools\GetAgentFeedback;
use App\Mcp\Tools\ListAgentFeedback;
use App\Models\Administrator;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_an_administrator_key_work_the_reports_of_every_account(): void
    {
        Queue::fake();
        $report = AgentFeedbackReport::factory()->create(['title' => 'Search ignores accents']);
        $administrator = $this->holding(['*', 'administration'], administrator: true);

        MonicaServer::actingAs($administrator)->tool(ListAgentFeedback::class, [])->assertOk()->assertSee('Search ignores accents');
        MonicaServer::actingAs($administrator)->tool(GetAgentFeedback::class, ['report' => $report->uuid])->assertOk()->assertSee('Search ignores accents');
        MonicaServer::actingAs($administrator)->tool(AddAgentFeedbackNote::class, ['report' => $report->uuid, 'body' => 'Confirmed.'])->assertOk();
        MonicaServer::actingAs($administrator)->tool(ChangeAgentFeedbackStatus::class, ['report' => $report->uuid, 'status' => 'accepted'])->assertOk();

        $this->assertEquals('accepted', $report->refresh()->status->value);
    }

    #[Test]
    public function it_keeps_the_reports_closed_to_a_key_without_the_administration_scope(): void
    {
        $administrator = $this->holding(['*'], administrator: true);

        MonicaServer::actingAs($administrator)->tool(ListAgentFeedback::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_keeps_the_reports_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $dwight = $this->holding(['*', 'administration'], administrator: false);

        MonicaServer::actingAs($dwight)->tool(ListAgentFeedback::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_reports_an_unknown_report_as_not_existing(): void
    {
        $administrator = $this->holding(['*', 'administration'], administrator: true);

        MonicaServer::actingAs($administrator)->tool(GetAgentFeedback::class, ['report' => 'not-a-uuid'])->assertHasErrors(['That report does not exist.']);
    }

    /** @param  list<string>  $abilities */
    private function holding(array $abilities, bool $administrator): User
    {
        $user = User::factory()->create();

        if ($administrator) {
            Administrator::factory()->create(['user_id' => $user->id]);
        }

        $plainText = $user->createToken('Scranton script', $abilities)->plainTextToken;

        return $user->withAccessToken(PersonalAccessToken::findToken($plainText));
    }
}
