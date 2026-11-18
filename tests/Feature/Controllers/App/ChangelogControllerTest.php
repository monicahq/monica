<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Helpers\ChangelogEntries;
use App\Models\Account;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogControllerTest extends TestCase
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
    public function it_shows_what_changed_and_marks_it_seen(): void
    {
        $user = $this->signedIn();

        $response = $this->get(route('changelog.index'));

        $response->assertOk();
        $response->assertSee('What changed in Monica, day by day.');
        $response->assertSee('Book the conference room from a contact');
        $response->assertSee('2 fixes');
        $this->assertDatabaseHas('changelog_seen_entries', ['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);
    }

    #[Test]
    public function it_counts_the_unseen_changes_in_the_top_bar(): void
    {
        $this->signedIn();

        $this->get(route('settings.index'))->assertSee('What’s new, 5 changes you have not seen');

        $this->get(route('changelog.index'));

        $this->get(route('settings.index'))->assertDontSee('changes you have not seen');
    }

    #[Test]
    public function it_shows_what_changed_to_a_visitor_without_votes_or_comments(): void
    {
        $response = $this->get(route('changelog.index'));

        $response->assertOk();
        $response->assertSee('Book the conference room from a contact');
        $response->assertSee(route('auth.login.new'));
        $response->assertDontSee('Is this change useful to you?');
        $response->assertDontSee('Loading the comments');
        $response->assertDontSee('Tell us which changes help you');
        $response->assertDontSee(route('notifications.index'));
        $this->assertDatabaseCount('changelog_seen_entries', 0);
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'created_at' => '2026-01-01 00:00:00']);

        $this->actingAs($user);

        return $user;
    }
}
