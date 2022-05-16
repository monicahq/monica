<?php

namespace Tests\Unit\Models;

use App\Models\GroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
