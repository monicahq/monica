<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $address = ContactAddress::factory()->create();

        $this->assertTrue($address->contact()->exists());
    }
}
