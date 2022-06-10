<?php

namespace Tests\Unit\Models;

use App\Models\GiftState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftStateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $state = GiftState::factory()->create();

        $this->assertTrue($state->account()->exists());
    }
}
