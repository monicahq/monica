<?php

namespace Tests\Unit\Models;

use App\Models\GroupType;
use App\Models\GroupTypeRole;
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

    /** @test */
    public function it_has_many_group_type_roles()
    {
        $groupType = GroupType::factory()->create();
        GroupTypeRole::factory(2)->create([
            'group_type_id' => $groupType->id,
        ]);

        $this->assertTrue($groupType->groupTypeRoles()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $groupType = GroupType::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $groupType->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $groupType = GroupType::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $groupType->label
        );
    }
}
