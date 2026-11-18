<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\AgentFeedbackReport;
use App\Models\ChangelogComment;
use App\Models\SupportRequest;
use App\ViewModels\Admin\AdminNavigationViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNavigationViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_only_the_support_requests_still_to_handle(): void
    {
        SupportRequest::factory()->count(2)->toHandle()->create();
        SupportRequest::factory()->waitingOnUser()->create();
        SupportRequest::factory()->solved()->create();

        $this->assertSame(2, (new AdminNavigationViewModel)->supportToHandleCount());
    }

    #[Test]
    public function it_counts_only_the_changelog_comments_nobody_has_reviewed(): void
    {
        ChangelogComment::factory()->create();
        ChangelogComment::factory()->reviewed()->create();
        ChangelogComment::factory()->hidden()->create();

        $this->assertSame(1, (new AdminNavigationViewModel)->changelogToReviewCount());
    }

    #[Test]
    public function it_counts_only_the_new_agent_feedback_reports(): void
    {
        AgentFeedbackReport::factory()->count(2)->create();
        AgentFeedbackReport::factory()->accepted()->create();

        $this->assertSame(2, (new AdminNavigationViewModel)->newAgentFeedbackCount());
    }
}
