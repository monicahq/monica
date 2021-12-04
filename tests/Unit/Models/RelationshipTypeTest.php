<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_group_type()
    {
        $type = RelationshipType::factory()->create();

        $this->assertTrue($type->groupType()->exists());
    }
}
