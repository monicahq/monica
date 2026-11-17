<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReviewChangelogComment;
use App\Enums\AdministratorActionEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewChangelogCommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_comment_out_of_the_comments_to_review(): void
    {
        Queue::fake();
        $administrator = User::factory()->create();
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $comment = ChangelogComment::factory()->create();

        new ReviewChangelogComment(administrator: $administrator, comment: $comment)->execute();

        $this->assertSame(ChangelogCommentStatusEnum::Reviewed, $comment->refresh()->status);
        $this->assertNotNull($comment->reviewed_at);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::ChangelogCommentReviewed);
    }

    #[Test]
    public function it_refuses_somebody_who_is_not_an_administrator(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ReviewChangelogComment(administrator: User::factory()->create(), comment: ChangelogComment::factory()->create())->execute();
    }
}
