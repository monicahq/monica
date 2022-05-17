<?php

namespace Tests\Unit\Models;

use App\Models\Pet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PetTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_pet_category()
    {
        $pet = Pet::factory()->create();

        $this->assertTrue($pet->petCategory()->exists());
    }

    /** @test */
    public function it_has_one_contact()
    {
        $pet = Pet::factory()->create();

        $this->assertTrue($pet->contact()->exists());
    }
}
