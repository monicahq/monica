<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueListItem;
use App\Enums\PhoneLabelEnum;
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

class UpdateValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_choice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'label' => 'Beet farmer',
        ]);

        $result = new UpdateValueListItem(
            user: $user,
            item: $item,
            label: 'Beet farm owner',
            description: 'Grows beets, sells beets',
            colour: '#AA151B',
            icon: 'sprout',
        )->execute();

        $this->assertInstanceOf(ValueListItem::class, $result);

        $item->refresh();

        $this->assertEquals('Beet farm owner', $item->label);
        $this->assertEquals('Grows beets, sells beets', $item->description);
        $this->assertEquals('#aa151b', $item->colour);
        $this->assertEquals('sprout', $item->icon);
    }

    #[Test]
    public function it_takes_a_supplied_choice_off_its_translation_key_when_it_is_renamed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->supplied(PhoneLabelEnum::Mobile)->create([
            'account_id' => $user->account_id,
        ]);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();

        $item->refresh();

        $this->assertNull($item->label_translation_key);
        $this->assertEquals('Cell', $item->label);
    }

    #[Test]
    public function it_leaves_how_used_the_choice_is_and_whether_it_is_offered_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->disabled()->used(4)->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();

        $item->refresh();

        $this->assertFalse($item->is_active);
        $this->assertSame(4, $item->usage_count);
        $this->assertSame(3, $item->position);
    }

    #[Test]
    public function it_refuses_a_choice_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $item = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();
    }

    #[Test]
    public function it_logs_what_changed_and_what_it_changed_from(): void
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
            'label' => 'Mobile',
        ]);

        new UpdateValueListItem(user: $user, item: $item, label: 'Cell')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListItemUpdate
                && $job->loggable->is($item)
                && $job->parameters === ['label' => 'Cell', 'name' => 'Phone labels']
                && $job->changes === [
                    'label' => ['before' => 'Mobile', 'after' => 'Cell'],
                ],
        );
    }
}
