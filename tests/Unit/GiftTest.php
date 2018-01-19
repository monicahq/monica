<?php

namespace Tests\Unit;

use App\Gift;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GiftTest extends TestCase
{
    use DatabaseTransactions;

    public function test_toggle_a_gift_idea()
    {
        $gift = new Gift;
        $gift->is_an_idea = true;
        $gift->toggle();

        $this->assertEquals(
            false,
            $gift->is_an_idea
        );

        $this->assertEquals(
            true,
            $gift->has_been_offered
        );

        $this->assertEquals(
            false,
            $gift->has_been_received
        );
    }

    public function test_toggle_a_gift_offered()
    {
        $gift = new Gift;
        $gift->has_been_offered = true;
        $gift->toggle();

        $this->assertEquals(
            true,
            $gift->is_an_idea
        );

        $this->assertEquals(
            false,
            $gift->has_been_offered
        );

        $this->assertEquals(
            false,
            $gift->has_been_received
        );
    }
}
