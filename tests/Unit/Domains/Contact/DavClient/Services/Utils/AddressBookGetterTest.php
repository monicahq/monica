<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Services\Utils\AddressBookGetter;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClientException;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavServerNotCompliantException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\DavTester;
use Tests\TestCase;

class AddressBookGetterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_address_book_data()
    {
        $tester = (new DavTester)
            ->addressBookBaseUri()
            ->capabilities()
            ->displayName()
            ->fake();
        $client = $tester->client();
        $result = (new AddressBookGetter)
            ->withClient($client)
            ->execute();

        $tester->assert();
        $this->assertEquals([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
            'capabilities' => [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
            'name' => 'Test',
        ], $result);
    }

    /** @test */
    public function it_get_address_book_data_direct()
    {
        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
            ->resourceTypeAddressBook('https://test/dav/addressbooks/user@test.com/contacts/')
            ->optionsOk('https://test/dav/addressbooks/user@test.com/contacts/')
            ->capabilities()
            ->displayName()
            ->fake();
        $client = $tester->client();
        $result = (new AddressBookGetter)
            ->withClient($client)
            ->execute();

        $tester->assert();
        $this->assertEquals([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
            'capabilities' => [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
            'name' => 'Test',
        ], $result);
    }

    /** @test */
    public function it_fails_on_server_not_compliant()
    {
        $tester = (new DavTester)
            ->userPrincipalEmpty()
            ->serviceUrl()
            ->optionsFail()
            ->fake();
        $client = $tester->client();

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter)
            ->withClient($client)
            ->execute();
    }

    /** @test */
    public function it_fails_if_no_userprincipal()
    {
        $tester = (new DavTester)
            ->userPrincipalEmpty()
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipalEmpty()
            ->fake();
        $client = $tester->client();

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter)
            ->withClient($client)
            ->execute();
    }

    /** @test */
    public function it_fails_if_no_addressbook()
    {
        $tester = (new DavTester)
            ->userPrincipalEmpty()
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookEmpty()
            ->fake();
        $client = $tester->client();

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter)
            ->withClient($client)
            ->execute();
    }

    /** @test */
    public function it_fails_if_no_addressbook_url()
    {
        $tester = (new DavTester)
            ->userPrincipalEmpty()
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookHome()
            ->resourceTypeHomeOnly()
            ->optionsOk()
            ->fake();
        $client = $tester->client();

        $this->expectException(DavClientException::class);
        (new AddressBookGetter)
            ->withClient($client)
            ->execute();
    }
}
