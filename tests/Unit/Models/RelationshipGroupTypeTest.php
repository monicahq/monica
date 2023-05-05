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

    /** @test */
    public function it_gets_the_default_name()
    {
        $groupType = RelationshipGroupType::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $groupType->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $groupType = RelationshipGroupType::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $groupType->name
        );
    }
}
