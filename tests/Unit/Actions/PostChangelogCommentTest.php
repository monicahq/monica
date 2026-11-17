<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PostChangelogComment;
use App\Helpers\ChangelogEntries;
use App\Models\ChangelogComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostChangelogCommentTest extends TestCase
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
    public function it_posts_a_comment_waiting_for_review(): void
    {
        $user = User::factory()->create();

        $comment = new PostChangelogComment(user: $user, entryId: '01M7NBFWK5XNH908RFDGEAJXKN', body: '<b>Bears</b>, beets, Battlestar Galactica.', isPrivate: false, isAnonymous: true)->execute();

        $this->assertInstanceOf(ChangelogComment::class, $comment);
        $this->assertDatabaseHas('changelog_comments', [
            'user_id' => $user->id,
            'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN',
            'body' => 'Bears, beets, Battlestar Galactica.',
            'is_private' => false,
            'is_anonymous' => true,
            'status' => 'new',
        ]);
    }

    #[Test]
    public function it_never_makes_a_private_comment_anonymous(): void
    {
        $comment = new PostChangelogComment(user: User::factory()->create(), entryId: '01M7NBFWK5XNH908RFDGEAJXKN', body: 'For the team only.', isPrivate: true, isAnonymous: true)->execute();

        $this->assertTrue($comment->is_private);
        $this->assertFalse($comment->is_anonymous);
    }

    #[Test]
    public function it_refuses_a_comment_left_empty_once_cleaned(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PostChangelogComment(user: User::factory()->create(), entryId: '01M7NBFWK5XNH908RFDGEAJXKN', body: '<p></p>', isPrivate: false, isAnonymous: false)->execute();
    }

    #[Test]
    public function it_refuses_a_change_that_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new PostChangelogComment(user: User::factory()->create(), entryId: '01M7NBFWM0EKND6XPWKYPCYYN4', body: 'Hello.', isPrivate: false, isAnonymous: false)->execute();
    }
}
