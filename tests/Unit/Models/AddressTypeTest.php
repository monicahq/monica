<?php

namespace Tests\Unit\Models;

use App\Models\AddressType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $type = AddressType::factory()->create();

        $this->assertTrue($type->account()->exists());
    }
}
