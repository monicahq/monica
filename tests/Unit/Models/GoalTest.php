<?php

namespace Tests\Unit\Models;

use App\Models\Goal;
use App\Models\Streak;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $goal = Goal::factory()->create();

        $this->assertTrue($goal->contact()->exists());
    }

    /** @test */
    public function it_has_many_streaks(): void
    {
        $goal = Goal::factory()->create();
        Streak::factory()->count(2)->create([
            'goal_id' => $goal->id,
        ]);

        $this->assertTrue($goal->streaks()->exists());
    }
}
