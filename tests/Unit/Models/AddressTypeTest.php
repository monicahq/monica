<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AddressType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
