<?php

namespace Tests\Unit\Models;

use App\Models\GroupTypeRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GroupTypeRoleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_group_type()
    {
        $groupTypeRole = GroupTypeRole::factory()->create();

        $this->assertTrue($groupTypeRole->groupType()->exists());
    }
}
