<?php

namespace Tests\Unit\Helpers;

use App\Helpers\GoalHelper;
use App\Models\Goal;
use App\Models\Streak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoalHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_streak_information(): void
    {
        Carbon::setTestNow(Carbon::create(2020, 1, 9));

        $goal = Goal::factory()->create();
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2019-12-29',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2019-12-31',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2019-12-30',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-01',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-02',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-03',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-04',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-06',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-07',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-08',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2020-01-09',
        ]);
        Streak::factory()->create([
            'goal_id' => $goal->id,
            'happened_at' => '2019-01-09',
        ]);

        $this->assertEquals(
            [
                'max_streak' => 7,
                'current_streak' => 3,
            ],
            GoalHelper::getStreakData($goal)
        );
    }
}
