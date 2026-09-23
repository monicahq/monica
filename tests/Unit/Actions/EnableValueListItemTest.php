<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisableValueListItem;
use App\Actions\EnableValueListItem;
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
    public function it_puts_the_choice_back_in_the_place_it_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        new DisableValueListItem(user: $user, item: $item)->execute();
        new EnableValueListItem(user: $user, item: $item)->execute();

        $item->refresh();

        $this->assertTrue($item->is_active);
        $this->assertSame(3, $item->position);
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
}
