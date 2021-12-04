<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\RelationshipType;
use App\Helpers\RelationshipHelper;
use App\Models\RelationshipGroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_reverse_relationships()
    {
        $groupType = RelationshipGroupType::factory()->create();
        $relationshipTypeFatherSon = RelationshipType::factory()->create([
            'name' => 'father',
            'name_reverse_relationship' => 'son',
            'relationship_group_type_id' => $groupType->id,
        ]);
        $relationshipTypeSonFather = RelationshipType::factory()->create([
            'name' => 'son',
            'name_reverse_relationship' => 'father',
            'relationship_group_type_id' => $groupType->id,
        ]);

        $type = RelationshipHelper::getReverseRelationshipType($relationshipTypeFatherSon);

        $this->assertEquals(
            $relationshipTypeSonFather->id,
            $type->id,
        );

        $this->assertInstanceOf(
            RelationshipType::class,
            $type
        );
    }
}
