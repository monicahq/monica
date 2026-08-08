<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueListPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateValueListPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_list_to_the_position_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $third = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        $result = new UpdateValueListPosition(user: $user, valueList: $third, position: 1)->execute();

        $this->assertInstanceOf(ValueList::class, $result);
        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);
    }

    #[Test]
    public function it_puts_the_list_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateValueListPosition(user: $user, valueList: $first, position: 12)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_lists_of_other_accounts_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $elsewhere = ValueList::factory()->create(['position' => 7]);

        new UpdateValueListPosition(user: $user, valueList: $list, position: 1)->execute();

        $this->assertSame(7, $elsewhere->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_list_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateValueListPosition(user: $user, valueList: $list, position: 1)->execute();
    }

    #[Test]
    public function it_logs_the_move(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dundie awards',
        ]);

        new UpdateValueListPosition(user: $user, valueList: $list, position: 1)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListPositionUpdate
                && $job->parameters === ['name' => 'Dundie awards'],
        );
    }
}
