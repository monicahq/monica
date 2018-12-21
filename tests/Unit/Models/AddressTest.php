<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->contact()->exists());
    }

    public function test_it_belongs_to_a_place()
    {
        $address = factory(Address::class)->create([]);
        $this->assertTrue($address->place()->exists());
    }
}
