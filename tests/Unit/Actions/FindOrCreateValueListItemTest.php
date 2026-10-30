<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\FindOrCreateValueListItem;
use App\Enums\PhoneLabelEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ValueListItemIsStopped;
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

class FindOrCreateValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_the_choice_a_wording_names_whatever_its_case_and_spaces(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Paper company',
        ]);

        $found = new FindOrCreateValueListItem(
            user: $user,
            valueList: $list,
            wording: '  paper COMPANY ',
        )->execute();

        $this->assertTrue($found->is($item));
        $this->assertSame(1, ValueListItem::query()->where('value_list_id', $list->id)->count());
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_finds_a_supplied_choice_by_the_wording_the_reader_sees(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->supplied(PhoneLabelEnum::Mobile)->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
        ]);

        $found = new FindOrCreateValueListItem(
            user: $user,
            valueList: $list,
            wording: 'mobile',
        )->execute();

        $this->assertTrue($found->is($item));
    }

    #[Test]
    public function it_adds_a_wording_no_choice_names_and_records_it_in_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Contact categories',
        ]);

        $item = new FindOrCreateValueListItem(
            user: $user,
            valueList: $list,
            wording: ' Finer Things Club ',
        )->execute();

        $this->assertDatabaseHas('value_list_items', [
            'id' => $item->id,
            'value_list_id' => $list->id,
            'label' => 'Finer Things Club',
            'is_active' => true,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemCreation && $job->isAbout($item),
        );
    }

    #[Test]
    public function it_refuses_a_wording_naming_a_choice_no_longer_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Party Planning Committee',
        ]);

        $this->expectException(ValueListItemIsStopped::class);

        new FindOrCreateValueListItem(
            user: $user,
            valueList: $list,
            wording: 'party planning committee',
        )->execute();
    }

    #[Test]
    public function it_keeps_a_choice_no_longer_offered_that_the_field_already_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Party Planning Committee',
        ]);

        $found = new FindOrCreateValueListItem(
            user: $user,
            valueList: $list,
            wording: 'Party Planning Committee',
            heldItemIds: [$item->id],
        )->execute();

        $this->assertTrue($found->is($item));
        $this->assertFalse($found->is_active);
    }

    #[Test]
    public function it_refuses_to_add_to_a_list_that_does_not_take_choices_of_the_users_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'allows_user_values' => false,
        ]);

        $this->expectException(AuthorizationException::class);

        new FindOrCreateValueListItem(user: $user, valueList: $list, wording: 'Scranton Strangler')->execute();
    }

    #[Test]
    public function it_refuses_a_list_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new FindOrCreateValueListItem(user: $user, valueList: $list, wording: 'Scranton Strangler')->execute();
    }
}
