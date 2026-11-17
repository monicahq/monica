<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReplyToChangelogComment;
use App\Enums\AdministratorActionEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\ChangelogComment;
use App\Models\User;
use App\Notifications\ChangelogReplySent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplyToChangelogCommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_a_public_comment_under_it_and_reviews_it(): void
    {
        Queue::fake();
        Notification::fake();
        $comment = ChangelogComment::factory()->create();

        $reply = new ReplyToChangelogComment(administrator: $this->administrator(), comment: $comment, body: 'We built it for exactly that, Kevin.')->execute();

        $this->assertDatabaseHas('changelog_comment_replies', [
            'id' => $reply->id,
            'changelog_comment_id' => $comment->id,
            'author_name' => 'Jim Halpert',
            'is_public' => true,
        ]);
        $this->assertSame(ChangelogCommentStatusEnum::Reviewed, $comment->refresh()->status);
        Notification::assertNothingSent();
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::ChangelogCommentReplied);
    }

    #[Test]
    public function it_emails_the_answer_to_a_private_comment(): void
    {
        Queue::fake();
        Notification::fake();
        $comment = ChangelogComment::factory()->private()->create();

        $reply = new ReplyToChangelogComment(administrator: $this->administrator(), comment: $comment, body: 'The city comes from your provider.')->execute();

        $this->assertFalse($reply->is_public);
        Notification::assertSentTo($comment->user, ChangelogReplySent::class, fn (ChangelogReplySent $notification): bool => $notification->body === 'The city comes from your provider.');
    }

    #[Test]
    public function it_refuses_somebody_who_is_not_an_administrator(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ReplyToChangelogComment(administrator: User::factory()->create(), comment: ChangelogComment::factory()->create(), body: 'Hello.')->execute();
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
