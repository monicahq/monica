<?php

namespace Tests\Unit\Services\Contact\Address;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Address\UpdateAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_an_address()
    {
        $address = factory(Address::class)->create([]);

        $request = [
            'account_id' => $address->account_id,
            'contact_id' => $address->contact_id,
            'address_id' => $address->id,
            'name' => 'this is a test',
            'street' => '1990 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '',
            'longitude' => '',
        ];

        $address = app(UpdateAddress::class)->execute($request);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'account_id' => $address->account_id,
            'name' => 'this is a test',
        ]);

        $this->assertEquals(
            '1990 Lafayette Street',
            $address->place->street
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $address = factory(Address::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateAddress::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_contact_is_archived()
    {
        $contact = factory(Contact::class)->state('archived')->create();
        $address = factory(Address::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $request = [
            'account_id' => $address->account_id,
            'contact_id' => $address->contact_id,
            'address_id' => $address->id,
            'name' => 'this is a test',
            'street' => '1990 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '',
            'longitude' => '',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateAddress::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_address_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([]);
        $address = factory(Address::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateAddress::class)->execute($request);
    }
}
