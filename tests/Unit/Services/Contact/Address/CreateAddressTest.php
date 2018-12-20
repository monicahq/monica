<?php

namespace Tests\Unit\Services\Contact\Address;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Services\Contact\Address\CreateAddress;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Contact\Contact;

class CreateAddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_an_address()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'work address',
            'street' => '199 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '',
            'longitude' => '',
        ];

        $addressService = new CreateAddress;
        $address = $addressService->execute($request);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'account_id' => $contact->account_id,
            'name' => 'work address',
        ]);

        $this->assertEquals(
            '199 Lafayette Street',
            $address->place->street
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(MissingParameterException::class);
        (new CreateAddress)->execute($request);
    }
}
