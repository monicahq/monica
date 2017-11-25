<?php

namespace Tests\Unit;

use App\Address;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetPartialAddressReturnsNullIfNoCityIsDefined()
    {
        $address = new Address;

        $this->assertNull($address->getPartialAddress());
    }

    public function testGetPartialAddressReturnsCityIfProvinceIsUndefined()
    {
        $address = new Address;
        $address->city = 'Montreal';

        $this->assertEquals(
            'Montreal',
            $address->getPartialAddress()
        );
    }

    public function testGetPartialAddressReturnsCityAndProvince()
    {
        $address = new Address;
        $address->city = 'Montreal';
        $address->province = 'QC';

        $this->assertEquals(
            'Montreal, QC',
            $address->getPartialAddress()
        );
    }

    public function testGetCountryReturnsNullIfNoStreetIsDefined()
    {
        $address = new Address;

        $this->assertNull($address->getCountryName());
    }

    public function testGetCountryCodeReturnsStreetWhenDefined()
    {
        $address = new Address;
        $address->country_id = 1;

        $this->assertEquals(
            'United States',
            $address->getCountryName()
        );
    }

    public function testGetCountryISOReturnsNullIfISONotFound()
    {
        $address = new Address;
        $address->country_id = null;

        $this->assertNull($address->getCountryISO());
    }

    public function testGetCountryISOReturnsTheRightISO()
    {
        $address = new Address;
        $address->country_id = 1;

        $this->assertEquals(
            'us',
            $address->getCountryISO()
        );
    }

    public function testGetGoogleMapsAddressReturnsLink()
    {
        $address = new Address;
        $address->country_id = 1;
        $address->name = 'default';
        $address->street = '12';
        $address->city = 'Scranton';
        $address->province = null;
        $address->postal_code = '90210';
        $address->country_id = 1;

        $this->assertEquals(
            'https://www.google.ca/maps/place/'.urlencode($address->getFullAddress()),
            $address->getGoogleMapAddress()
        );
    }
}
