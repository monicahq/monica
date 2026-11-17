<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\HideChangelogComment;
use App\Enums\AdministratorActionEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HideChangelogCommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_a_public_comment(): void
    {
        Queue::fake();
        $comment = ChangelogComment::factory()->create();

        new HideChangelogComment(administrator: $this->administrator(), comment: $comment)->execute();

        $this->assertSame(ChangelogCommentStatusEnum::Hidden, $comment->refresh()->status);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::ChangelogCommentHidden);
    }

    #[Test]
    public function it_refuses_to_hide_a_private_comment(): void
    {
        $this->expectException(AuthorizationException::class);

        new HideChangelogComment(administrator: $this->administrator(), comment: ChangelogComment::factory()->private()->create())->execute();
    }

    #[Test]
    public function it_refuses_somebody_who_is_not_an_administrator(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new HideChangelogComment(administrator: User::factory()->create(), comment: ChangelogComment::factory()->create())->execute();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
