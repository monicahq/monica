<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PetCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_only_common_pets()
    {
        $petCategory = new PetCategory;

        $this->assertEquals(
          3,
          $petCategory->common()->count()
        );
    }

    /** @test */
    public function it_gets_pet_category_name()
    {
        $petCategory = new PetCategory;
        $petCategory->name = 'Rgis';

        $this->assertEquals(
          'Rgis',
          $petCategory->name
        );
    }
}
