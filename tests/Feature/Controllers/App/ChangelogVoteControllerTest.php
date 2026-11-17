<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogVote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogVoteControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string ENTRY = '01M7NBFWK5XNH908RFDGEAJXKN';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_casts_and_withdraws_a_vote(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from(route('changelog.index'))
            ->put(route('changelog.vote.update', ['entry' => self::ENTRY]), ['direction' => 'up'])
            ->assertRedirect(route('changelog.index'));

        $this->assertDatabaseHas('changelog_votes', ['user_id' => $user->id, 'entry_id' => self::ENTRY, 'direction' => 'up']);

        $this->delete(route('changelog.vote.destroy', ['entry' => self::ENTRY]))->assertRedirect();

        $this->assertSame(0, ChangelogVote::query()->count());
    }

    #[Test]
    public function it_refuses_a_direction_it_does_not_know(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('changelog.vote.update', ['entry' => self::ENTRY]), ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    #[Test]
    public function it_answers_not_found_for_a_change_that_does_not_exist(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('changelog.vote.update', ['entry' => '01M7NBFWM0EKND6XPWKYPCYYN4']), ['direction' => 'up'])
            ->assertNotFound();
    }
}
