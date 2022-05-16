<?php

namespace Tests\Unit\Models;

use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RelationshipGroupTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $groupType = RelationshipGroupType::factory()->create();

        $this->assertTrue($groupType->account()->exists());
    }

    /** @test */
    public function it_has_many_types()
    {
        $groupType = RelationshipGroupType::factory()->create();
        RelationshipType::factory()->count(2)->create([
            'relationship_group_type_id' => $groupType->id,
        ]);

        $this->assertTrue($groupType->types()->exists());
    }
}
