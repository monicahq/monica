<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels;

use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use App\Models\User;
use App\ViewModels\ChangelogCommentsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogCommentsViewModelTest extends TestCase
{
    use RefreshDatabase;

    private const string ENTRY = '01M7NBFWK5XNH908RFDGEAJXKN';

    #[Test]
    public function it_leaves_out_what_others_wrote_privately_and_what_the_team_hid(): void
    {
        $jim = User::factory()->create();
        $public = ChangelogComment::factory()->create(['entry_id' => self::ENTRY, 'body' => 'Identity theft is not a joke.']);
        ChangelogComment::factory()->private()->create(['entry_id' => self::ENTRY]);
        ChangelogComment::factory()->hidden()->create(['entry_id' => self::ENTRY]);
        $mine = ChangelogComment::factory()->private()->create(['entry_id' => self::ENTRY, 'user_id' => $jim->id]);
        ChangelogComment::factory()->create(['entry_id' => '01M7NBFWK7BEESY8WABBMXF3E6']);

        $comments = new ChangelogCommentsViewModel(user: $jim, entryId: self::ENTRY)->comments();

        $this->assertSame([$public->uuid, $mine->uuid], array_column($comments, 'id'));
        $this->assertTrue($comments[1]['isMine']);
        $this->assertTrue($comments[1]['isPrivate']);
    }

    #[Test]
    public function it_names_the_author_of_an_anonymous_comment_to_nobody(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        ChangelogComment::factory()->anonymous()->create(['entry_id' => self::ENTRY, 'user_id' => $dwight->id]);

        $asJim = new ChangelogCommentsViewModel(user: User::factory()->create(), entryId: self::ENTRY)->comments();
        $asDwight = new ChangelogCommentsViewModel(user: $dwight, entryId: self::ENTRY)->comments();

        $this->assertSame('Anonymous', $asJim[0]['authorName']);
        $this->assertFalse($asJim[0]['isMine']);
        $this->assertSame('Anonymous', $asDwight[0]['authorName']);
        $this->assertTrue($asDwight[0]['isMine']);
    }

    #[Test]
    public function it_shows_the_public_replies_only(): void
    {
        $comment = ChangelogComment::factory()->create(['entry_id' => self::ENTRY]);
        ChangelogCommentReply::factory()->create(['changelog_comment_id' => $comment->id, 'body' => 'Thank you, Michael.']);
        ChangelogCommentReply::factory()->emailed()->create(['changelog_comment_id' => $comment->id]);

        $comments = new ChangelogCommentsViewModel(user: User::factory()->create(), entryId: self::ENTRY)->comments();

        $this->assertSame(['Thank you, Michael.'], array_column($comments[0]['replies'], 'body'));
    }
}
