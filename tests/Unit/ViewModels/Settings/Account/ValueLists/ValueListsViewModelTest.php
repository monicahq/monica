<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\ValueLists;

use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\ViewModels\Settings\Account\ValueLists\ValueListsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_lists_of_the_account_in_order(): void
    {
        $user = User::factory()->create();
        ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Phone labels',
            'position' => 2,
        ]);
        ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Job titles',
            'position' => 1,
        ]);

        $lists = new ValueListsViewModel(user: $user)->lists();

        $this->assertCount(2, $lists);
        $this->assertSame('Job titles', $lists[0]['name']);
        $this->assertSame('Phone labels', $lists[1]['name']);
    }

    #[Test]
    public function it_says_a_list_can_be_deleted_only_when_the_product_did_not_supply_it(): void
    {
        $user = User::factory()->create();
        ValueList::factory()->supplied(ValueListEnum::Genders)->create([
            'account_id' => $user->account_id,
            'position' => 1,
        ]);
        ValueList::factory()->create([
            'account_id' => $user->account_id,
            'position' => 2,
        ]);

        $lists = new ValueListsViewModel(user: $user)->lists();

        $this->assertFalse($lists[0]['canBeDeleted']);
        $this->assertTrue($lists[1]['canBeDeleted']);
    }

    #[Test]
    public function it_leaves_out_the_lists_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        ValueList::factory()->create(['account_id' => $dwight->account_id]);

        $this->assertCount(0, new ValueListsViewModel(user: $michael)->lists());
    }

    #[Test]
    public function it_gives_the_values_of_a_list_in_order(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Receptionist',
            'position' => 2,
        ]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Salesman',
            'position' => 1,
        ]);

        $values = new ValueListsViewModel(user: $user)->lists()[0]['values'];

        $this->assertSame('Salesman', $values[0]['label']);
        $this->assertSame('Receptionist', $values[1]['label']);
    }

    #[Test]
    public function it_counts_the_values_of_a_list(): void
    {
        $user = User::factory()->create();
        $empty = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $one = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $several = ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $one->id]);
        ValueListItem::factory()->count(3)->create(['account_id' => $user->account_id, 'value_list_id' => $several->id]);

        $lists = new ValueListsViewModel(user: $user)->lists();

        $this->assertSame($empty->id, $lists[0]['id']);
        $this->assertSame('no values', $lists[0]['countLabel']);
        $this->assertSame('1 value', $lists[1]['countLabel']);
        $this->assertSame('3 values', $lists[2]['countLabel']);
    }

    #[Test]
    public function it_says_how_much_a_value_is_leaned_on(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 1]);
        ValueListItem::factory()->used(1)->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 2]);
        ValueListItem::factory()->used(4)->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 3]);

        $values = new ValueListsViewModel(user: $user)->lists()[0]['values'];

        $this->assertSame('not used yet', $values[0]['usageLabel']);
        $this->assertSame('used once', $values[1]['usageLabel']);
        $this->assertSame('used 4 times', $values[2]['usageLabel']);
    }

    #[Test]
    public function it_says_a_value_can_be_deleted_only_while_nothing_points_at_it(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 1]);
        ValueListItem::factory()->used()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 2]);

        $values = new ValueListsViewModel(user: $user)->lists()[0]['values'];

        $this->assertTrue($values[0]['canBeDeleted']);
        $this->assertFalse($values[1]['canBeDeleted']);
    }

    #[Test]
    public function it_says_whether_a_value_is_still_active(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 1]);
        ValueListItem::factory()->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'position' => 2]);

        $values = new ValueListsViewModel(user: $user)->lists()[0]['values'];

        $this->assertTrue($values[0]['isActive']);
        $this->assertFalse($values[1]['isActive']);
    }

    #[Test]
    public function it_gives_the_user_to_the_sidebar(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->is(new ValueListsViewModel(user: $user)->user()));
    }
}
