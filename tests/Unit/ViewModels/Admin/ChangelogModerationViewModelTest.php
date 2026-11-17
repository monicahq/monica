<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogComment;
use App\Models\ChangelogVote;
use App\Models\User;
use App\ViewModels\Admin\ChangelogModerationViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogModerationViewModelTest extends TestCase
{
    use RefreshDatabase;

    private const string FEATURE = '01M7NBFWK5XNH908RFDGEAJXKN';

    private const string FIX = '01M7NBFWK7BEESY8WABBMXF3E6';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_counts_the_comments_of_each_tab(): void
    {
        ChangelogComment::factory()->count(2)->create(['entry_id' => self::FEATURE]);
        ChangelogComment::factory()->private()->reviewed()->create(['entry_id' => self::FEATURE]);
        ChangelogComment::factory()->hidden()->create(['entry_id' => self::FIX]);

        $tabs = new ChangelogModerationViewModel()->tabs();

        $this->assertSame(['To review', 'Private', 'Hidden', 'All comments', 'Votes'], array_column($tabs, 'label'));
        $this->assertSame([2, 1, 1, 4, null], array_column($tabs, 'count'));
    }

    #[Test]
    public function it_lists_the_changes_holding_matching_comments_and_opens_the_first(): void
    {
        ChangelogComment::factory()->create(['entry_id' => self::FEATURE]);
        ChangelogComment::factory()->create(['entry_id' => self::FIX]);
        ChangelogVote::factory()->count(3)->create(['entry_id' => self::FEATURE]);
        ChangelogVote::factory()->down()->create(['entry_id' => self::FEATURE]);

        $viewModel = new ChangelogModerationViewModel;
        $rows = $viewModel->rows();

        $this->assertSame([self::FEATURE, self::FIX], array_column($rows, 'id'));
        $this->assertSame([3, 1], [$rows[0]['up'], $rows[0]['down']]);
        $this->assertSame(self::FEATURE, $viewModel->current()['id'] ?? null);
        $this->assertSame('75% of 4 people who voted like this change.', $viewModel->current()['ratioLine'] ?? null);
    }

    #[Test]
    public function it_names_the_real_author_of_an_anonymous_comment(): void
    {
        $kevin = User::factory()->create(['first_name' => 'Kevin', 'last_name' => 'Malone', 'email' => 'kevin@dundermifflin.com']);
        ChangelogComment::factory()->anonymous()->create(['entry_id' => self::FEATURE, 'user_id' => $kevin->id]);

        $comment = new ChangelogModerationViewModel()->comments()[0];

        $this->assertSame('Kevin Malone', $comment['author']);
        $this->assertStringContainsString('kevin@dundermifflin.com', $comment['who']);
        $this->assertStringContainsString('Shown as Anonymous on What’s new', $comment['who']);
    }

    #[Test]
    public function it_filters_by_search_type_and_visibility(): void
    {
        $angela = User::factory()->create(['first_name' => 'Angela', 'last_name' => 'Martin']);
        ChangelogComment::factory()->create(['entry_id' => self::FEATURE, 'user_id' => $angela->id]);
        ChangelogComment::factory()->private()->create(['entry_id' => self::FIX, 'body' => 'The fax machine still eats mine.']);

        $this->assertSame([self::FEATURE], array_column(new ChangelogModerationViewModel(tab: 'all', search: 'Angela')->rows(), 'id'));
        $this->assertSame([self::FIX], array_column(new ChangelogModerationViewModel(tab: 'all', search: 'fax machine')->rows(), 'id'));
        $this->assertSame([self::FIX], array_column(new ChangelogModerationViewModel(tab: 'all', type: 'fix')->rows(), 'id'));
        $this->assertSame([self::FEATURE], array_column(new ChangelogModerationViewModel(tab: 'all', visibility: 'public')->rows(), 'id'));
    }

    #[Test]
    public function it_says_nothing_is_left_to_review(): void
    {
        $viewModel = new ChangelogModerationViewModel;

        $this->assertSame([], $viewModel->rows());
        $this->assertNull($viewModel->current());
        $this->assertSame('Nothing to review', $viewModel->emptyTitle());
    }
}
