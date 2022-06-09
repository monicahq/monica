<?php

namespace Tests\Unit\Models;

use App\Models\GiftOccasion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftOccasionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $occasion = GiftOccasion::factory()->create();

        $this->assertTrue($occasion->account()->exists());
    }
}
