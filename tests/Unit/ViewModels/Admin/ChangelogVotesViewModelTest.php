<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogVote;
use App\ViewModels\Admin\ChangelogVotesViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogVotesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sorts_the_changes_by_the_order_asked_for(): void
    {
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        ChangelogVote::factory()->count(2)->create(['entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);
        ChangelogVote::factory()->down()->count(3)->create(['entry_id' => '01M7NBFWK7BEESY8WABBMXF3E6']);

        $this->assertSame('01M7NBFWK7BEESY8WABBMXF3E6', new ChangelogVotesViewModel(sort: 'down')->rows()[0]['id']);
        $this->assertSame('01M7NBFWK5XNH908RFDGEAJXKN', new ChangelogVotesViewModel(sort: 'up')->rows()[0]['id']);
        $this->assertSame('01M7NBFWKB8S8MJ0MBVV165X36', new ChangelogVotesViewModel(sort: 'new')->rows()[0]['id']);
        $this->assertSame('5 votes on 4 changes published in the last 30 days.', new ChangelogVotesViewModel()->summary());
        $this->assertSame('down', ChangelogVotesViewModel::sortFrom('sideways'));
    }
}
