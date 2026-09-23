<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateLifeEventCategoryPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventCategoryPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category_to_the_front(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering', 'position' => 2]);

        new UpdateLifeEventCategoryPosition(user: $user, lifeEventCategory: $second, position: 1)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventCategoryPositionUpdate
                && $job->parameters === ['name' => 'Volunteering'];
        });
    }

    #[Test]
    public function it_places_a_category_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateLifeEventCategoryPosition(user: $user, lifeEventCategory: $category, position: 99)->execute();

        $this->assertSame(2, $category->refresh()->position);
    }

    #[Test]
    public function it_numbers_every_category_from_one_again_counting_the_retired_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $retired = LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'position' => 5]);
        $moved = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 9]);

        new UpdateLifeEventCategoryPosition(user: $user, lifeEventCategory: $moved, position: 1)->execute();

        $this->assertSame(1, $moved->refresh()->position);
        $this->assertSame(2, $retired->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_categories_of_another_account_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $moved = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $elsewhere = LifeEventCategory::factory()->create(['account_id' => $stranger->account_id, 'position' => 7]);

        new UpdateLifeEventCategoryPosition(user: $user, lifeEventCategory: $moved, position: 1)->execute();

        $this->assertSame(7, $elsewhere->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $dwight->account_id, 'position' => 3]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new UpdateLifeEventCategoryPosition(user: $michael, lifeEventCategory: $category, position: 1)->execute();
        } finally {
            $this->assertSame(3, $category->refresh()->position);
        }
    }
}
