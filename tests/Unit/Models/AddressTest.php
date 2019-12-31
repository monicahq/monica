<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->contact()->exists());
    }

    /** @test */
    public function it_belongs_to_a_place()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->place()->exists());
    }
}
