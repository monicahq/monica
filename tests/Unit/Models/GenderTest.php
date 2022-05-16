<?php

namespace Tests\Unit\Models;

use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
