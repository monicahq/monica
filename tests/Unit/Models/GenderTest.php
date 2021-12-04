<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $gender = Gender::factory()->create();

        $this->assertTrue($gender->account()->exists());
    }
}
