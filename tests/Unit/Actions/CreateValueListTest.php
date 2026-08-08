<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateValueList;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateValueListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $list = new CreateValueList(
            user: $user,
            name: 'Dundie awards',
            description: 'The categories handed out every year',
        )->execute();

        $this->assertInstanceOf(ValueList::class, $list);
        $this->assertDatabaseHas('value_lists', [
            'id' => $list->id,
            'account_id' => $user->account_id,
            'key' => null,
            'name' => 'Dundie awards',
            'name_translation_key' => null,
            'description' => 'The categories handed out every year',
            'is_system' => false,
            'allows_user_values' => true,
            'position' => 1,
        ]);
    }

    #[Test]
    public function it_puts_a_new_list_at_the_end_of_the_settings_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        ValueList::factory()->create(['account_id' => $user->account_id, 'position' => 4]);
        ValueList::factory()->create(['position' => 11]);

        $list = new CreateValueList(user: $user, name: 'Dundie awards')->execute();

        $this->assertSame(5, $list->position);
    }

    #[Test]
    public function it_can_be_closed_to_choices_of_the_users_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $list = new CreateValueList(
            user: $user,
            name: 'Dundie awards',
            allowsUserValues: false,
        )->execute();

        $this->assertFalse($list->allows_user_values);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $list = new CreateValueList(
            user: $user,
            name: '<script>alert("Dwight")</script>Dundie awards',
            description: '  ',
        )->execute();

        $this->assertEquals('Dundie awards', $list->name);
        $this->assertNull($list->description);
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $list = new CreateValueList(user: $user, name: 'Dundie awards')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ValueListCreation
                && $job->account->id === $user->account_id
                && $job->loggable->is($list)
                && $job->parameters === ['name' => 'Dundie awards'],
        );
    }
}
