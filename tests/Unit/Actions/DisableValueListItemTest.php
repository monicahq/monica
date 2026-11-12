<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisableValueListItem;
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

class DisableValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_offering_the_choice(): void
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

        $result = new DisableValueListItem(user: $user, item: $item)->execute();

        $this->assertInstanceOf(ValueListItem::class, $result);
        $this->assertFalse($item->refresh()->is_active);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemDisabled
                && $job->parameters === ['label' => 'Fax', 'name' => 'Phone labels'],
        );
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DisableValueListItem(user: $user, item: $item)->execute();
    }
}
