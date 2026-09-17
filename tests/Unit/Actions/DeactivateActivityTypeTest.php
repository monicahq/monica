<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateActivityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_whether_the_type_is_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        $returned = new DeactivateActivityType(user: $user, activityType: $type)->execute();

        $this->assertInstanceOf(ActivityType::class, $returned);
        $this->assertDatabaseHas('activity_types', ['id' => $type->id, 'is_active' => false]);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        new DeactivateActivityType(user: $user, activityType: $type)->execute();
        new DeactivateActivityType(user: $user, activityType: $type)->execute();

        $this->assertDatabaseHas('activity_types', ['id' => $type->id, 'is_active' => false]);
    }

    #[Test]
    public function it_leaves_the_position_and_the_name_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Sales call',
            'position' => 4,
        ]);

        new DeactivateActivityType(user: $user, activityType: $type)->execute();

        $this->assertDatabaseHas('activity_types', ['id' => $type->id, 'name' => 'Sales call', 'position' => 4]);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateActivityType(user: $michael, activityType: $type)->execute();
        } finally {
            $this->assertDatabaseHas('activity_types', ['id' => $type->id, 'is_active' => true]);
        }
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundie awards']);

        new DeactivateActivityType(user: $user, activityType: $type)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ActivityTypeDeactivated
            && $job->parameters === ['name' => 'Dundie awards']);
    }
}
