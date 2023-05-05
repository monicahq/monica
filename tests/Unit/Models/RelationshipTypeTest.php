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

    /** @test */
    public function it_gets_the_default_name()
    {
        $relationshipType = RelationshipType::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $relationshipType->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $relationshipType = RelationshipType::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $relationshipType->name
        );
    }

    /** @test */
    public function it_gets_the_default_name_reverse_relationship()
    {
        $relationshipType = RelationshipType::factory()->create([
            'name_reverse_relationship' => null,
            'name_reverse_relationship_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $relationshipType->name_reverse_relationship
        );
    }

    /** @test */
    public function it_gets_the_custom_name_reverse_relationship_if_defined()
    {
        $relationshipType = RelationshipType::factory()->create([
            'name_reverse_relationship' => 'this is the real name',
            'name_reverse_relationship_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $relationshipType->name_reverse_relationship
        );
    }
}
