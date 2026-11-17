<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CastChangelogVote;
use App\Enums\ChangelogVoteDirectionEnum;
use App\Helpers\ChangelogEntries;
use App\Models\ChangelogVote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CastChangelogVoteTest extends TestCase
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
    public function it_casts_a_vote_and_switches_it_the_other_way(): void
    {
        $user = User::factory()->create();

        $vote = new CastChangelogVote(user: $user, entryId: '01M7NBFWK7BEESY8WABBMXF3E6', direction: ChangelogVoteDirectionEnum::Up)->execute();
        new CastChangelogVote(user: $user, entryId: '01M7NBFWK7BEESY8WABBMXF3E6', direction: ChangelogVoteDirectionEnum::Down)->execute();

        $this->assertInstanceOf(ChangelogVote::class, $vote);
        $this->assertSame(1, ChangelogVote::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('changelog_votes', ['user_id' => $user->id, 'entry_id' => '01M7NBFWK7BEESY8WABBMXF3E6', 'direction' => 'down']);
    }

    #[Test]
    public function it_refuses_a_change_nobody_can_see_yet(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CastChangelogVote(user: User::factory()->create(), entryId: '01M7NBFWKFAM1JXYF0A7RKJ865', direction: ChangelogVoteDirectionEnum::Up)->execute();
    }
}
