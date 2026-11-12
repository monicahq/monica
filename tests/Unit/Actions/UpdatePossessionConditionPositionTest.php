<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionConditionPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionConditionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_condition_up_and_shifts_the_others_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $new = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $used = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Used', 'position' => 2]);
        $mint = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 3]);

        $moved = new UpdatePossessionConditionPosition(
            user: $user,
            possessionCondition: $mint,
            position: 1,
        )->execute();

        $this->assertEquals(1, $moved->position);
        $this->assertEquals(2, $new->refresh()->position);
        $this->assertEquals(3, $used->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionConditionPositionUpdate
                && $job->parameters === ['name' => 'Mint'],
        );
    }

    #[Test]
    public function it_puts_a_condition_moved_past_the_end_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $new = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $used = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Used', 'position' => 2]);

        new UpdatePossessionConditionPosition(
            user: $user,
            possessionCondition: $new,
            position: 40,
        )->execute();

        $this->assertEquals(1, $used->refresh()->position);
        $this->assertEquals(2, $new->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_condition_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionConditionPosition(
            user: User::factory()->create(),
            possessionCondition: PossessionCondition::factory()->create(),
            position: 1,
        )->execute();
    }
}
