<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Emotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
