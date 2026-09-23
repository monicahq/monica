<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueListItemPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateValueListItemPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_choice_to_the_position_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Phone labels',
        ]);
        $first = $this->item($user->account_id, $list->id, 1);
        $second = $this->item($user->account_id, $list->id, 2);
        $third = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Fax',
            'position' => 3,
        ]);

        $result = new UpdateValueListItemPosition(user: $user, item: $third, position: 1)->execute();

        $this->assertInstanceOf(ValueListItem::class, $result);
        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemPositionUpdate
                && $job->parameters === ['label' => 'Fax', 'name' => 'Phone labels'],
        );
    }

    #[Test]
    public function it_puts_the_choice_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $first = $this->item($user->account_id, $list->id, 1);
        $second = $this->item($user->account_id, $list->id, 2);

        new UpdateValueListItemPosition(user: $user, item: $first, position: 12)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_numbers_a_disabled_choice_along_with_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $disabled = ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'position' => 1,
        ]);
        $item = $this->item($user->account_id, $list->id, 2);

        new UpdateValueListItemPosition(user: $user, item: $item, position: 1)->execute();

        $this->assertSame(1, $item->refresh()->position);
        $this->assertSame(2, $disabled->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_choices_of_other_lists_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = $this->item($user->account_id, $list->id, 1);
        $elsewhere = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'position' => 7,
        ]);

        new UpdateValueListItemPosition(user: $user, item: $item, position: 1)->execute();

        $this->assertSame(7, $elsewhere->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateValueListItemPosition(user: $user, item: $item, position: 1)->execute();
    }

    private function item(int $accountId, int $listId, int $position): ValueListItem
    {
        return ValueListItem::factory()->create([
            'account_id' => $accountId,
            'value_list_id' => $listId,
            'position' => $position,
        ]);
    }
}
