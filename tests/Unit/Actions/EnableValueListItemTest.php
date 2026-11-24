<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EnableValueListItem;
use App\Enums\CountryEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnableValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_choice_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Phone labels',
        ]);
        $item = ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Fax',
        ]);

        $result = new EnableValueListItem(user: $user, item: $item)->execute();

        $this->assertInstanceOf(ValueListItem::class, $result);
        $this->assertTrue($item->refresh()->is_active);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemEnabled
                && $job->parameters === ['label' => 'Fax', 'name' => 'Phone labels'],
        );
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->disabled()->create();

        $this->expectException(ModelNotFoundException::class);

        new EnableValueListItem(user: $user, item: $item)->execute();
    }

    #[Test]
    public function it_refuses_to_offer_a_country_the_list_already_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $stopped = ValueListItem::factory()->supplied(CountryEnum::France)->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'FR']);

        try {
            new EnableValueListItem(user: $user, item: $stopped)->execute();
            $this->fail('France was offered twice.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('active', $e->errors());
        }

        $this->assertFalse($stopped->refresh()->is_active);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_offers_a_country_again_when_only_a_stopped_value_holds_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $stopped = ValueListItem::factory()->supplied(CountryEnum::France)->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        ValueListItem::factory()->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'FR']);

        new EnableValueListItem(user: $user, item: $stopped)->execute();

        $this->assertTrue($stopped->refresh()->is_active);
    }
}
