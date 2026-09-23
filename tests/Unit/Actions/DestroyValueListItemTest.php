<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyValueListItem;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_choice_nothing_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Phone labels',
        ]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Fax',
        ]);

        new DestroyValueListItem(user: $user, item: $item)->execute();

        $this->assertSoftDeleted('value_list_items', ['id' => $item->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemDeletion
                && $job->parameters === ['label' => 'Fax', 'name' => 'Phone labels'],
        );
    }

    #[Test]
    public function it_refuses_a_choice_that_records_already_use(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->used(3)->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyValueListItem(user: $user, item: $item)->execute();
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyValueListItem(user: $user, item: $item)->execute();
    }
}
