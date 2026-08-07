<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyUser;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_another_user_of_the_account(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $author = User::factory()->create(['account_id' => $account->id]);
        $colleague = User::factory()->create(['account_id' => $account->id]);

        new DestroyUser(
            author: $author,
            user: $colleague,
        )->execute();

        $this->assertSoftDeleted($colleague);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserDeletion
                && $job->account->id === $account->id
                && $job->user->id === $author->id
                && $job->parameters === ['email' => $colleague->email],
        );
    }

    #[Test]
    public function it_throws_when_the_author_deletes_themselves(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = User::factory()->create();

        new DestroyUser(
            author: $author,
            user: $author,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = User::factory()->create();
        $stranger = User::factory()->create();

        new DestroyUser(
            author: $author,
            user: $stranger,
        )->execute();
    }
}
