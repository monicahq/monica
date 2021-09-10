<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Tests\Helpers\DavTester;
use App\Services\DavClient\Utils\Dav\Client;
use App\Services\DavClient\Utils\AddressBookGetter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Dav\DavClientException;
use App\Services\DavClient\Utils\Dav\DavServerNotCompliantException;

class AddressBookGetterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_address_book_data()
    {
        $tester = (new DavTester())
            ->addressBookBaseUri()
            ->capabilities()
            ->displayName();
        $client = new Client([], $tester->getClient());
        $result = (new AddressBookGetter($client))
            ->getAddressBookData();

        $tester->assert();
        $this->assertEquals([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
            'capabilities' => [
                'addressbookMultiget' => false,
                'addressbookQuery' => false,
                'syncCollection' => false,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0'
                ]
            ],
            'name' => 'Test',
        ], $result);
    }

    /** @test */
    public function it_fails_on_server_not_compliant()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsFail();
        $client = new Client([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter($client))
            ->getAddressBookData();
    }

    /** @test */
    public function it_fails_if_no_userprincipal()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipalEmpty();

        $client = new Client([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter($client))
            ->getAddressBookData();
    }

    /** @test */
    public function it_fails_if_no_addressbook()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookEmpty();

        $client = new Client([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter($client))
            ->getAddressBookData();
    }

    /** @test */
    public function it_fails_if_no_addressbook_url()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookHome()
            ->resourceTypeHomeOnly();
        $client = new Client([], $tester->getClient());

        $this->expectException(DavClientException::class);
        (new AddressBookGetter($client))
            ->getAddressBookData();
    }
}
