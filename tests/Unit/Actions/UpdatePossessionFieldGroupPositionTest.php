<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionFieldGroupPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldGroupPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_third_group_to_the_first_position_and_numbers_the_groups_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $first = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase', 'position' => 1]);
        $second = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Warranty', 'position' => 2]);
        $third = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Technical details', 'position' => 3]);

        $moved = new UpdatePossessionFieldGroupPosition(
            user: $user,
            group: $third,
            position: 1,
        )->execute();

        $this->assertInstanceOf(PossessionFieldGroup::class, $moved);
        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldGroupUpdate
                && $job->parameters === ['name' => 'Technical details', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_leaves_the_groups_of_another_type_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $first = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        $second = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);
        $other = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id, 'position' => 5]);

        new UpdatePossessionFieldGroupPosition(
            user: $user,
            group: $second,
            position: 1,
        )->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(5, $other->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $group = PossessionFieldGroup::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionFieldGroupPosition(
            user: $user,
            group: $group,
            position: 1,
        )->execute();
    }
}
