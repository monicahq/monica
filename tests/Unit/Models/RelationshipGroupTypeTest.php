<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\RelationshipType;
use App\Models\RelationshipGroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
