<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyValueList;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Exceptions\CannotDeleteASuppliedList;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyValueListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_list_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        new DestroyValueList(user: $user, valueList: $list)->execute();

        $this->assertSoftDeleted('value_lists', ['id' => $list->id]);
    }

    #[Test]
    public function it_takes_the_choices_of_the_list_down_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
        ]);
        $elsewhere = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        new DestroyValueList(user: $user, valueList: $list)->execute();

        $this->assertSoftDeleted('value_list_items', ['id' => $item->id]);
        $this->assertNotSoftDeleted('value_list_items', ['id' => $elsewhere->id]);
    }

    #[Test]
    public function it_refuses_a_list_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyValueList(user: $user, valueList: $list)->execute();
    }

    #[Test]
    public function it_refuses_a_list_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->supplied(ValueListEnum::Genders)->create(['account_id' => $user->account_id]);

        $this->expectException(CannotDeleteASuppliedList::class);

        new DestroyValueList(user: $user, valueList: $list)->execute();
    }

    #[Test]
    public function it_logs_the_deletion_with_the_name_the_list_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dundie awards',
        ]);

        new DestroyValueList(user: $user, valueList: $list)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListDeletion
                && $job->parameters === ['name' => 'Dundie awards'],
        );
    }
}
