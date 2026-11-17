<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MarkChangelogSeen;
use App\Helpers\ChangelogEntries;
use App\Models\ChangelogSeenEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkChangelogSeenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_every_unseen_change_published_since_the_person_joined(): void
    {
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        $user = User::factory()->create(['created_at' => '2026-11-01 00:00:00']);
        ChangelogSeenEntry::factory()->create(['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);

        new MarkChangelogSeen(user: $user)->execute();
        new MarkChangelogSeen(user: $user)->execute();

        $this->assertSame(4, ChangelogSeenEntry::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('changelog_seen_entries', ['user_id' => $user->id, 'entry_id' => '01M7NBFWKB8S8MJ0MBVV165X36']);
        $this->assertDatabaseMissing('changelog_seen_entries', ['user_id' => $user->id, 'entry_id' => '01M7NBFWKD8ZJ060BP6ZNF2NFG']);
        $this->assertDatabaseMissing('changelog_seen_entries', ['user_id' => $user->id, 'entry_id' => '01M7NBFWKFAM1JXYF0A7RKJ865']);
    }
}
