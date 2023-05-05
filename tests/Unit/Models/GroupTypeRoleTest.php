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

    /** @test */
    public function it_gets_the_default_label()
    {
        $groupTypeRole = GroupTypeRole::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $groupTypeRole->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $groupTypeRole = GroupTypeRole::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $groupTypeRole->label
        );
    }
}
