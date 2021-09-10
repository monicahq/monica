<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;

class DavTester
{
    /**
     * @var array
     */
    public $responses;

    /**
     * @var string
     */
    public $baseUri;

    public function __construct(string $baseUri = 'https://test')
    {
        $this->baseUri = $baseUri;
        $this->responses = [];
    }

    public function getClient()
    {
        $mock = new MockHandler($this->responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack, 'base_uri' => $this->baseUri]);
    }

    public function addressBookBaseUri()
    {
        return $this->serviceUrl()
        ->options()
        ->userPrincipal()
        ->addressbookHome()
        ->resourceTypeAddressBook();
    }

    public function capabilities()
    {
        return $this->supportedReportSet()
            ->supportedAddressData();
    }

    public function serviceUrl()
    {
        $this->responses[] = new Response(301, ['Location' => $this->baseUri]);

        return $this;
    }

    public function options()
    {
        $this->responses[] = new Response(200, ['Dav' => '1, 3, addressbook']);

        return $this;
    }

    public function optionsFail()
    {
        $this->responses[] = new Response(200, ['Dav' => 'bad']);

        return $this;
    }

    public function userPrincipal()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:current-user-principal>'.
                        '<d:href>/dav/principals/user@test.com/</d:href>'.
                    '</d:current-user-principal>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function userPrincipalEmpty()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:current-user-principal/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function addressbookHome()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/principals/user@test.com/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<card:addressbook-home-set>'.
                        '<d:href>/dav/addressbooks/user@test.com/</d:href>'.
                    '</card:addressbook-home-set>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function addressbookEmpty()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/principals/user@test.com/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<card:addressbook-home-set/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function resourceTypeAddressBook()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:resourcetype>'.
                        '<card:addressbook/>'.
                    '</d:resourcetype>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function resourceTypeHomeOnly()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:resourcetype/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function resourceTypeEmpty()
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:resourcetype/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function supportedReportSet(array $reportSet = ['card:addressbook-multiget', 'card:addressbook-query', 'd:sync-collection'])
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:supported-report-set>'.
                    implode('', array_map(function ($report) {
                        return "<$report/>";
                    }, $reportSet)).
                    '</d:supported-report-set>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function supportedAddressData(array $list = ['card:address-data-type content-type="text/vcard" version="4.0"'])
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<card:supported-address-data>'.
                    implode('', array_map(function ($item) {
                        return "<$item/>";
                    }, $list)).
                    '</card:supported-address-data>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    public function displayName(string $name = 'Test')
    {
        $this->responses[] = new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:displayname>$name</d:displayname>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>');

        return $this;
    }

    private function multistatusHeader()
    {
        return '<d:multistatus xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">';
    }
}
