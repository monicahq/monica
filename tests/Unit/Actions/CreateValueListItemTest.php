<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateValueListItem;
use App\Enums\CountryEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_choice_to_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Contact categories',
        ]);

        $item = new CreateValueListItem(
            user: $user,
            valueList: $list,
            label: 'Beet farmer',
            description: 'Grows beets, sells beets',
            colour: '#AA151B',
            icon: 'sprout',
        )->execute();

        $this->assertInstanceOf(ValueListItem::class, $item);
        $this->assertDatabaseHas('value_list_items', [
            'id' => $item->id,
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => null,
            'label' => 'Beet farmer',
            'label_translation_key' => null,
            'description' => 'Grows beets, sells beets',
            'colour' => '#aa151b',
            'icon' => 'sprout',
            'position' => 1,
            'is_system' => false,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemCreation
                && $job->isAbout($item)
                && $job->parameters === ['label' => 'Beet farmer', 'name' => 'Contact categories'],
        );
    }

    #[Test]
    public function it_puts_a_new_choice_at_the_end_of_its_own_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'position' => 4,
        ]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'position' => 9]);

        $item = new CreateValueListItem(user: $user, valueList: $list, label: 'Beet farmer')->execute();

        $this->assertSame(5, $item->position);
    }

    #[Test]
    public function it_refuses_a_list_that_does_not_take_choices_of_the_users_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'allows_user_values' => false,
        ]);

        $this->expectException(AuthorizationException::class);

        new CreateValueListItem(user: $user, valueList: $list, label: 'Beet farmer')->execute();
    }

    #[Test]
    public function it_refuses_a_list_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateValueListItem(user: $user, valueList: $list, label: 'Beet farmer')->execute();
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $item = new CreateValueListItem(
            user: $user,
            valueList: $list,
            label: '<script>alert("Dwight")</script>Beet farmer',
            description: '  ',
        )->execute();

        $this->assertEquals('Beet farmer', $item->label);
        $this->assertNull($item->description);
        $this->assertNull($item->colour);
    }

    #[Test]
    public function it_stores_the_code_of_a_country_in_capitals(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        $item = new CreateValueListItem(user: $user, valueList: $list, label: 'Japan', countryCode: 'jp')->execute();

        $this->assertDatabaseHas('value_list_items', ['id' => $item->id, 'country_code' => 'JP']);
    }

    #[Test]
    public function it_refuses_a_country_code_on_a_list_that_holds_no_countries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Genders->value]);

        $this->expectException(InvalidArgumentException::class);

        new CreateValueListItem(user: $user, valueList: $list, label: 'Japan', countryCode: 'JP')->execute();
    }

    #[Test]
    public function it_stores_a_picked_country_without_a_label_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        $item = new CreateValueListItem(user: $user, valueList: $list, label: null, countryCode: 'lt')->execute();

        $this->assertDatabaseHas('value_list_items', ['id' => $item->id, 'label' => null, 'label_translation_key' => null, 'country_code' => 'LT']);
        $this->assertEquals('Lithuania', $item->label);
    }

    #[Test]
    public function it_refuses_a_country_the_list_already_offers(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        ValueListItem::factory()->supplied(CountryEnum::France)->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'JP']);

        foreach (['FR', 'jp'] as $code) {
            try {
                new CreateValueListItem(user: $user, valueList: $list, label: null, countryCode: $code)->execute();
                $this->fail("{$code} was added twice.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('country_code', $e->errors());
            }
        }

        $this->assertEquals(2, ValueListItem::query()->where('value_list_id', $list->id)->count());
    }

    #[Test]
    public function it_refuses_a_value_with_neither_a_label_nor_a_country(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        $this->expectException(InvalidArgumentException::class);

        new CreateValueListItem(user: $user, valueList: $list, label: '  ', countryCode: null)->execute();
    }
}
