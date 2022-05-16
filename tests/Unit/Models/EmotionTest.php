<?php

namespace Tests\Unit\Models;

use App\Models\Emotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmotionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $emotion = Emotion::factory()->create();

        $this->assertTrue($emotion->account()->exists());
    }
}
