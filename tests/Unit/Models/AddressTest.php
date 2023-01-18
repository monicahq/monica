<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_contacts()
    {
        $address = Address::factory()->create();
        $contact = Contact::factory()->create();
        $address->contacts()->attach($contact->id);

        $this->assertTrue($address->contacts()->exists());
    }

    /** @test */
    public function it_has_one_address_type()
    {
        $address = Address::factory()->create();

        $this->assertTrue($address->addressType()->exists());
    }
}
