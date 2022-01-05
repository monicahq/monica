<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
