<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogComment;
use App\Models\ChangelogVote;
use App\Models\User;
use App\ViewModels\ChangelogViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_groups_the_changes_of_one_utc_day_and_puts_the_fixes_last(): void
    {
        $days = $this->viewModel()->days();

        $this->assertCount(1, $days);
        $this->assertSame('2026-11-16', $days[0]['date']);
        $this->assertSame(['01M7NBFWKB8S8MJ0MBVV165X36', '01M7NBFWK5XNH908RFDGEAJXKN'], array_column($days[0]['entries'], 'id'));
        $this->assertSame(['01M7NBFWK9KQA94HRSS9BGJJ8K', '01M7NBFWK7BEESY8WABBMXF3E6'], array_column($days[0]['fixes'], 'id'));
        $this->assertSame('2 fixes', $days[0]['fixesHeading']);
    }

    #[Test]
    public function it_keeps_a_change_published_late_in_the_evening_on_its_utc_day_whatever_the_reader_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);

        $days = new ChangelogViewModel(user: $user, unseenIds: [], period: 30)->days();

        $this->assertContains('01M7NBFWKB8S8MJ0MBVV165X36', array_column($days[0]['entries'], 'id'));
        $this->assertSame('2026-11-16', $days[0]['date']);
    }

    #[Test]
    public function it_names_today_and_yesterday(): void
    {
        $days = $this->viewModel()->days();

        $this->assertSame('Yesterday', $days[0]['label']);
    }

    #[Test]
    public function it_counts_the_changes_of_the_period(): void
    {
        $this->assertSame(['days' => 1, 'features' => 1, 'improvements' => 1, 'fixes' => 2], $this->viewModel(period: 30)->figures());
        $this->assertSame(['days' => 2, 'features' => 2, 'improvements' => 1, 'fixes' => 2], $this->viewModel(period: 90)->figures());
    }

    #[Test]
    public function it_marks_what_the_person_had_not_seen_as_new(): void
    {
        $days = new ChangelogViewModel(user: User::factory()->create(), unseenIds: ['01M7NBFWKB8S8MJ0MBVV165X36'], period: 30)->days();

        $this->assertTrue($days[0]['entries'][0]['isNew']);
        $this->assertFalse($days[0]['entries'][1]['isNew']);
    }

    #[Test]
    public function it_draws_the_seen_divider_above_the_first_day_holding_nothing_new(): void
    {
        $days = new ChangelogViewModel(user: User::factory()->create(), unseenIds: ['01M7NBFWKB8S8MJ0MBVV165X36'], period: 90)->days();

        $this->assertSame([false, true], array_column($days, 'showsSeenDivider'));
    }

    #[Test]
    public function it_draws_no_divider_when_nothing_is_new(): void
    {
        $days = new ChangelogViewModel(user: User::factory()->create(), unseenIds: [], period: 90)->days();

        $this->assertSame([false, false], array_column($days, 'showsSeenDivider'));
    }

    #[Test]
    public function it_counts_the_public_comments_others_can_read_and_shows_the_reader_vote(): void
    {
        $user = User::factory()->create();
        $entryId = '01M7NBFWK5XNH908RFDGEAJXKN';
        ChangelogComment::factory()->count(2)->create(['entry_id' => $entryId]);
        ChangelogComment::factory()->private()->create(['entry_id' => $entryId]);
        ChangelogComment::factory()->hidden()->create(['entry_id' => $entryId]);
        ChangelogVote::factory()->down()->create(['user_id' => $user->id, 'entry_id' => $entryId]);

        $entry = new ChangelogViewModel(user: $user, unseenIds: [], period: 30)->days()[0]['entries'][1];

        $this->assertSame(2, $entry['commentCount']);
        $this->assertSame('2 comments', $entry['commentLabel']);
        $this->assertSame('down', $entry['vote']);
    }

    #[Test]
    public function it_gives_a_visitor_no_vote_and_no_comment_count(): void
    {
        $entryId = '01M7NBFWK5XNH908RFDGEAJXKN';
        ChangelogComment::factory()->count(2)->create(['entry_id' => $entryId]);

        $viewModel = new ChangelogViewModel(user: null, unseenIds: [], period: 30);
        $entry = $viewModel->days()[0]['entries'][1];

        $this->assertFalse($viewModel->allowsFeedback());
        $this->assertSame(0, $entry['commentCount']);
        $this->assertNull($entry['vote']);
    }

    private function viewModel(int $period = 30): ChangelogViewModel
    {
        return new ChangelogViewModel(user: User::factory()->create(), unseenIds: [], period: $period);
    }
}
