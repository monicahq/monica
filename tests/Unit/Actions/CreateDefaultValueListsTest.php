<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultValueLists;
use App\Enums\PhoneLabelEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultValueListsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_every_list_the_product_supplies(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $lists = new CreateDefaultValueLists(user: $user)->execute();

        $this->assertCount(count(ValueListEnum::cases()), $lists);

        foreach (ValueListEnum::cases() as $position => $case) {
            $this->assertDatabaseHas('value_lists', [
                'account_id' => $user->account_id,
                'key' => $case->value,
                'name' => null,
                'name_translation_key' => $case->label(),
                'description' => $case->description(),
                'is_system' => true,
                'allows_user_values' => true,
                'position' => $position + 1,
            ]);
        }

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListsSeeded
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_fills_every_list_with_the_choices_it_starts_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $list = ValueList::query()->firstWhere('key', ValueListEnum::PhoneLabels->value);

        $this->assertEquals(
            ['Mobile', 'Home', 'Work', 'Fax', 'Other', 'Custom...'],
            $list->items()->orderBy('position')->pluck('label_translation_key')->all(),
        );
        $this->assertEquals([1, 2, 3, 4, 5, 6], $list->items()->orderBy('position')->pluck('position')->all());
    }

    #[Test]
    public function it_ends_the_address_labels_with_the_one_that_takes_a_wording_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $list = ValueList::query()->firstWhere('key', ValueListEnum::AddressLabels->value);
        $items = $list->items()->orderBy('position')->get();

        $this->assertEquals(
            ['Home', 'Work', 'Holiday home', 'Other', 'Custom...'],
            $items->pluck('label_translation_key')->all(),
        );
        $this->assertTrue($items->last()->takesATypedWording());
    }

    #[Test]
    public function it_starts_the_long_lists_with_ten_choices_rather_than_all_there_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        foreach ([ValueListEnum::Countries, ValueListEnum::Languages, ValueListEnum::Currencies] as $case) {
            $list = ValueList::query()->firstWhere('key', $case->value);

            $this->assertCount(10, $list->items);
        }
    }

    #[Test]
    public function it_writes_the_code_of_a_currency_beside_its_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $list = ValueList::query()->firstWhere('key', ValueListEnum::Currencies->value);

        $this->assertSame('US dollar (USD)', $list->items()->orderBy('position')->first()->label);
    }

    #[Test]
    public function it_gives_every_supplied_choice_a_key_the_product_can_rely_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $item = ValueListItem::query()->firstWhere('key', PhoneLabelEnum::Mobile->value);

        $this->assertEquals($user->account_id, $item->account_id);
        $this->assertNull($item->getRawOriginal('label'));
        $this->assertTrue($item->is_system);
        $this->assertTrue($item->is_active);
        $this->assertSame(0, $item->usage_count);
    }

    #[Test]
    public function it_reads_the_supplied_lists_in_the_language_of_whoever_is_looking(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $this->app->setLocale('fr_FR');

        $list = ValueList::query()->firstWhere('key', ValueListEnum::ContactCategories->value);

        $this->assertEquals('Catégories de contact', $list->name);
        $this->assertEquals('Famille', $list->items()->orderBy('position')->first()->label);
    }

    #[Test]
    public function it_leaves_the_lists_of_other_accounts_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $elsewhere = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        $this->assertEquals(0, ValueList::query()->where('account_id', $elsewhere->account_id)->count());
    }

    #[Test]
    public function it_writes_nothing_when_the_account_already_has_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();
        new CreateDefaultValueLists(user: $user)->execute();

        $this->assertSame(count(ValueListEnum::cases()), ValueList::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_adds_only_the_lists_an_account_lacks(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultValueLists(user: $user)->execute();
        ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::AnimalSexes->value)->forceDelete();

        $added = new CreateDefaultValueLists(user: $user, lists: [ValueListEnum::Genders, ValueListEnum::AnimalSexes])->execute();

        $this->assertSame([ValueListEnum::AnimalSexes->value], array_map(fn (ValueList $list): string => $list->key, $added));
        $this->assertSame(1, ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::Genders->value)->count());
        $this->assertSame(3, ValueList::query()->where('key', ValueListEnum::AnimalSexes->value)->sole()->items()->count());
    }
}
