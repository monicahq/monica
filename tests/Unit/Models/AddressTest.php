<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $address = Address::factory()->create();

        $this->assertTrue($address->contact()->exists());
    }

    /** @test */
    public function it_has_one_address_type()
    {
        $address = Address::factory()->create();

        $this->assertTrue($address->addressType()->exists());
    }
}
