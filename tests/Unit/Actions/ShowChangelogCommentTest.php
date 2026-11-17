<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ShowChangelogComment;
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

class ShowChangelogCommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_hidden_comment_back_on_whats_new(): void
    {
        Queue::fake();
        $administrator = User::factory()->create();
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $comment = ChangelogComment::factory()->hidden()->create();

        new ShowChangelogComment(administrator: $administrator, comment: $comment)->execute();

        $this->assertSame(ChangelogCommentStatusEnum::Reviewed, $comment->refresh()->status);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::ChangelogCommentShown);
    }

    #[Test]
    public function it_refuses_somebody_who_is_not_an_administrator(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ShowChangelogComment(administrator: User::factory()->create(), comment: ChangelogComment::factory()->hidden()->create())->execute();
    }
}
