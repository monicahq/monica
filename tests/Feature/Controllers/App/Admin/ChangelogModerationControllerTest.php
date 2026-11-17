<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Helpers\ChangelogEntries;
use App\Models\Administrator;
use App\Models\ChangelogComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogModerationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string ENTRY = '01M7NBFWK5XNH908RFDGEAJXKN';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        Queue::fake();
    }

    #[Test]
    public function it_shows_the_comments_to_review(): void
    {
        $this->signedInAsAdministrator();
        ChangelogComment::factory()->create(['entry_id' => self::ENTRY, 'body' => 'Where are the turtles?']);

        $this->get(route('admin.changelog.index'))
            ->assertOk()
            ->assertSee('Comments and votes people left on What’s new.')
            ->assertSee('Where are the turtles?');
    }

    #[Test]
    public function it_shows_the_votes(): void
    {
        $this->signedInAsAdministrator();

        $this->get(route('admin.changelog.votes.index'))
            ->assertOk()
            ->assertSee('Book the conference room from a contact');
    }

    #[Test]
    public function it_reviews_hides_and_shows_a_comment(): void
    {
        $this->signedInAsAdministrator();
        $comment = ChangelogComment::factory()->create(['entry_id' => self::ENTRY]);

        $this->post(route('admin.changelog.comments.review.create', ['comment' => $comment->uuid]))->assertRedirect()->assertSessionHas('status', 'The comment is reviewed.');
        $this->post(route('admin.changelog.comments.hiding.create', ['comment' => $comment->uuid]))->assertRedirect()->assertSessionHas('status', 'The comment is hidden from What’s new.');
        $this->assertSame('hidden', $comment->refresh()->status->value);

        $this->delete(route('admin.changelog.comments.hiding.destroy', ['comment' => $comment->uuid]))->assertRedirect();
        $this->assertSame('reviewed', $comment->refresh()->status->value);
    }

    #[Test]
    public function it_tells_the_administrator_a_private_comment_cannot_be_hidden(): void
    {
        $this->signedInAsAdministrator();
        $comment = ChangelogComment::factory()->private()->create(['entry_id' => self::ENTRY]);

        $this->post(route('admin.changelog.comments.hiding.create', ['comment' => $comment->uuid]))->assertSessionHasErrors('comment');
    }

    #[Test]
    public function it_replies_to_a_comment(): void
    {
        $this->signedInAsAdministrator();
        $comment = ChangelogComment::factory()->create(['entry_id' => self::ENTRY]);

        $this->post(route('admin.changelog.comments.replies.create', ['comment' => $comment->uuid]), ['body' => 'Thank you, Pam.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Your reply is sent.');

        $this->post(route('admin.changelog.comments.replies.create', ['comment' => $comment->uuid]), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function it_refuses_a_reply_made_only_of_markup(): void
    {
        $this->signedInAsAdministrator();
        $comment = ChangelogComment::factory()->create(['entry_id' => self::ENTRY]);

        $this->post(route('admin.changelog.comments.replies.create', ['comment' => $comment->uuid]), ['body' => '<b></b>'])
            ->assertSessionHasErrors('body');
    }

    private function signedInAsAdministrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return $user;
    }
}
