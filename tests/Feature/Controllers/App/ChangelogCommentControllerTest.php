<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogCommentControllerTest extends TestCase
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
    public function it_draws_the_thread_the_page_loads_when_comments_are_opened(): void
    {
        $this->actingAs(User::factory()->create());
        ChangelogComment::factory()->create(['entry_id' => self::ENTRY, 'body' => 'I declare bankruptcy.']);

        $response = $this->get(route('changelog.comments.index', ['entry' => self::ENTRY]));

        $response->assertOk();
        $response->assertSee('id="changelog-comments-'.self::ENTRY.'"', escape: false);
        $response->assertSee('I declare bankruptcy.');
    }

    #[Test]
    public function it_posts_a_comment_and_answers_with_the_thread(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('changelog.comments.create', ['entry' => self::ENTRY]), [
            'body' => 'Could it remind me of Pretzel Day?',
            'visibility' => 'public',
            'identity' => 'anonymous',
        ]);

        $response->assertRedirect(route('changelog.comments.index', ['entry' => self::ENTRY]));
        $this->assertDatabaseHas('changelog_comments', ['user_id' => $user->id, 'entry_id' => self::ENTRY, 'is_anonymous' => true]);
    }

    #[Test]
    public function it_refuses_an_empty_comment(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('changelog.comments.create', ['entry' => self::ENTRY]), ['body' => '', 'visibility' => 'public'])
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function it_sends_a_comment_made_only_of_markup_back_to_the_thread_with_the_refusal(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('changelog.comments.create', ['entry' => self::ENTRY]), ['body' => '<b></b>', 'visibility' => 'public'])
            ->assertRedirect(route('changelog.comments.index', ['entry' => self::ENTRY]))
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function it_answers_not_found_for_a_change_that_does_not_exist(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('changelog.comments.index', ['entry' => '01M7NBFWM0EKND6XPWKYPCYYN4']))->assertNotFound();
    }
}
