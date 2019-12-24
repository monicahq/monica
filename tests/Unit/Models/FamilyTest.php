<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Family\Family;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $family = factory(Family::class)->create([]);

        $this->assertTrue($family->account()->exists());
    }
}
