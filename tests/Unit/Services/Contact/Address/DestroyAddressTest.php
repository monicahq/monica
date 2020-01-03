<?php

namespace Tests\Unit\Services\Contact\Address;

use Tests\TestCase;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Address\DestroyAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_an_address()
    {
        $address = factory(Address::class)->create([]);

        $request = [
            'account_id' => $address->account_id,
            'address_id' => $address->id,
        ];

        app(DestroyAddress::class)->execute($request);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_is_not_linked_to_address()
    {
        $contact = factory(Contact::class)->create([]);
        $address = factory(Address::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'address_id' => $address->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyAddress::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'address_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyAddress::class)->execute($request);
    }
}
