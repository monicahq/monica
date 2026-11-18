<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Helpers\AgentFeedbackQueue;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentFeedbackQueueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_new_reports_newest_first_and_counts_every_status(): void
    {
        $older = AgentFeedbackReport::factory()->create(['created_at' => now()->subDay()]);
        $newer = AgentFeedbackReport::factory()->create();
        AgentFeedbackReport::factory()->fixed()->create();

        $queue = new AgentFeedbackQueue;

        $this->assertSame([$newer->id, $older->id], $queue->query()->pluck('id')->all());
        $this->assertSame(['new' => 2, 'accepted' => 0, 'fixed' => 1, 'dismissed' => 0, 'all' => 3], $queue->counts());
    }

    #[Test]
    public function it_filters_by_kind_and_severity(): void
    {
        $match = AgentFeedbackReport::factory()->create(['kind' => AgentFeedbackKindEnum::MissingTool, 'severity' => AgentFeedbackSeverityEnum::Minor]);
        AgentFeedbackReport::factory()->create(['kind' => AgentFeedbackKindEnum::MissingTool, 'severity' => AgentFeedbackSeverityEnum::BlocksTask]);
        AgentFeedbackReport::factory()->create(['kind' => AgentFeedbackKindEnum::Bug, 'severity' => AgentFeedbackSeverityEnum::Minor]);

        $queue = new AgentFeedbackQueue(kind: 'missing_tool', severity: 'minor');

        $this->assertSame([$match->id], $queue->query()->pluck('id')->all());
    }

    #[Test]
    public function it_searches_the_title_the_tool_and_the_person(): void
    {
        $angela = User::factory()->create(['first_name' => 'Angela', 'last_name' => 'Martin', 'email' => 'angela@dundermifflin.com']);
        $byTitle = AgentFeedbackReport::factory()->create(['title' => 'Cats are refused as contacts', 'tool' => 'create-contact']);
        $byTool = AgentFeedbackReport::factory()->create(['title' => 'Something else', 'tool' => 'list-cats']);
        $byPerson = AgentFeedbackReport::factory()->create(['title' => 'Another thing', 'tool' => 'get-contact', 'user_id' => $angela->id, 'account_id' => $angela->account_id]);
        AgentFeedbackReport::factory()->create(['title' => 'Unrelated', 'tool' => 'get-vault']);

        $this->assertEqualsCanonicalizing([$byTitle->id, $byTool->id], new AgentFeedbackQueue(search: 'cats')->query()->pluck('id')->all());
        $this->assertSame([$byPerson->id], new AgentFeedbackQueue(search: 'dundermifflin')->query()->pluck('id')->all());
    }

    #[Test]
    public function it_falls_back_to_new_for_a_status_it_does_not_know(): void
    {
        $this->assertSame('new', new AgentFeedbackQueue(status: 'archived')->status);
    }
}
