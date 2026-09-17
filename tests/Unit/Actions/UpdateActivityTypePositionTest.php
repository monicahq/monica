<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateActivityTypePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateActivityTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_to_the_front_of_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $first = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        $second = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        new UpdateActivityTypePosition(user: $user, activityType: $second, position: 1)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_places_a_type_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);

        new UpdateActivityTypePosition(user: $user, activityType: $type, position: 99)->execute();

        $this->assertSame(2, $type->refresh()->position);
    }

    #[Test]
    public function it_numbers_the_category_from_one_again_counting_the_retired_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $retired = ActivityType::factory()->inactive()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 5]);
        $moved = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 9]);

        new UpdateActivityTypePosition(user: $user, activityType: $moved, position: 1)->execute();

        $this->assertSame(1, $moved->refresh()->position);
        $this->assertSame(2, $retired->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_types_of_another_category_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $moved = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 2]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id, 'position' => 1]);
        $untouched = ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $elsewhere->id, 'position' => 7]);

        new UpdateActivityTypePosition(user: $user, activityType: $moved, position: 1)->execute();

        $this->assertSame(7, $untouched->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $dwight->account_id, 'position' => 3]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new UpdateActivityTypePosition(user: $michael, activityType: $type, position: 1)->execute();
        } finally {
            $this->assertSame(3, $type->refresh()->position);
        }
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse visit']);

        new UpdateActivityTypePosition(user: $user, activityType: $type, position: 1)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ActivityTypePositionUpdate
            && $job->parameters === ['name' => 'Warehouse visit']);
    }
}
