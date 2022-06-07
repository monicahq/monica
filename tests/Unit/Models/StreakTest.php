<?php

namespace Tests\Unit\Models;

use App\Models\Streak;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_goal()
    {
        $streak = Streak::factory()->create();

        $this->assertTrue($streak->goal()->exists());
    }
}
