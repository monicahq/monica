<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionCondition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_condition_and_closes_the_gap_it_leaves(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $new = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        $damaged = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Damaged', 'position' => 2]);
        $broken = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Broken', 'position' => 3]);

        new DestroyPossessionCondition(
            user: $user,
            possessionCondition: $damaged,
        )->execute();

        $this->assertModelMissing($damaged);
        $this->assertEquals(1, $new->refresh()->position);
        $this->assertEquals(2, $broken->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionConditionDeletion
                && $job->parameters === ['name' => 'Damaged'],
        );
    }

    #[Test]
    public function it_refuses_a_condition_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionCondition(
            user: User::factory()->create(),
            possessionCondition: PossessionCondition::factory()->create(),
        )->execute();
    }
}
