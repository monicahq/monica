<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateValueList;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateValueListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dundie awards',
            'description' => 'The categories handed out every year',
        ]);

        $result = new UpdateValueList(
            user: $user,
            valueList: $list,
            name: 'Dundie award categories',
            description: 'Handed out at the party',
            allowsUserValues: false,
        )->execute();

        $this->assertInstanceOf(ValueList::class, $result);

        $list->refresh();

        $this->assertEquals('Dundie award categories', $list->name);
        $this->assertEquals('Handed out at the party', $list->description);
        $this->assertFalse($list->allows_user_values);
    }

    #[Test]
    public function it_takes_a_supplied_list_off_its_translation_key_when_it_is_renamed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create([
            'account_id' => $user->account_id,
        ]);

        new UpdateValueList(user: $user, valueList: $list, name: 'Numbers')->execute();

        $list->refresh();

        $this->assertNull($list->name_translation_key);

        $this->app->setLocale('fr_FR');

        $this->assertEquals('Numbers', $list->name);
    }

    #[Test]
    public function it_refuses_a_list_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateValueList(user: $user, valueList: $list, name: 'Numbers')->execute();
    }

    #[Test]
    public function it_logs_what_changed_and_what_it_changed_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dundie awards',
            'description' => 'Handed out at the party',
        ]);

        new UpdateValueList(
            user: $user,
            valueList: $list,
            name: 'Dundie award categories',
            description: 'Handed out at the party',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListUpdate
                && $job->loggable->is($list)
                && $job->parameters === ['name' => 'Dundie award categories']
                && $job->changes === [
                    'name' => ['before' => 'Dundie awards', 'after' => 'Dundie award categories'],
                ],
        );
    }
}
