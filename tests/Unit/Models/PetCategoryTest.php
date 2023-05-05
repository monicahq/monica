<?php

namespace Tests\Unit\Models;

use App\Models\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PetCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $petCategory = PetCategory::factory()->create();

        $this->assertTrue($petCategory->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_name()
    {
        $petCategory = PetCategory::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $petCategory->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $petCategory = PetCategory::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $petCategory->name
        );
    }
}
