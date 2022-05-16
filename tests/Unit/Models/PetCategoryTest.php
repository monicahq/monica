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
}
