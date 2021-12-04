<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GroupTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $type = GroupType::factory()->create();

        $this->assertTrue($type->account()->exists());
    }
}
