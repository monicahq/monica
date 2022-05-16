<?php

namespace Tests\Unit\Models;

use App\Models\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
