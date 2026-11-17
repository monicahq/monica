<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WithdrawChangelogVote;
use App\Helpers\ChangelogEntries;
use App\Models\ChangelogVote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WithdrawChangelogVoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_withdraws_the_vote_of_the_person_only(): void
    {
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        $jim = User::factory()->create();
        $vote = ChangelogVote::factory()->create(['user_id' => $jim->id, 'entry_id' => '01M7NBFWK7BEESY8WABBMXF3E6']);
        $pamVote = ChangelogVote::factory()->create(['entry_id' => '01M7NBFWK7BEESY8WABBMXF3E6']);

        new WithdrawChangelogVote(user: $jim, entryId: '01M7NBFWK7BEESY8WABBMXF3E6')->execute();

        $this->assertModelMissing($vote);
        $this->assertModelExists($pamVote);
    }
}
