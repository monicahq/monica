<?php

namespace Tests\Helpers;

use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DavTester extends TestCase
{
    public array $responses = [];

    private int $current = 0;

    public string $baseUri = '';

    public ?Factory $http = null;

    public function __construct(string $baseUri = 'https://test')
    {
        $this->baseUri = $baseUri;
    }

    public function client(): DavClient
    {
        return (new DavClient)->setBaseUri($this->baseUri);
    }

    public function fake()
    {
        $this->http = Http::fake(function ($request) {
            return $this->responses[$this->current++]['response'];
        });

        return $this;
    }

    public function assert()
    {
        Http::assertSentInOrder(array_map(function ($data) {
            return function (Request $request, Response $response) use ($data) {
                $srequest = $request->method().' '.$request->url();
                $this->assertEquals($data['method'], $request->method(), "method for request $srequest differs");
                $this->assertEquals($data['uri'], $request->url(), "uri for request $srequest differs");
                if (isset($data['body'])) {
                    $this->assertEquals($data['body'], $request->body(), "body for request $srequest differs");
                }
                if (isset($data['headers'])) {
                    foreach ($data['headers'] as $key => $value) {
                        $this->assertArrayHasKey($key, $request->headers(), "header $key for request $srequest is missing");
                        $this->assertEquals($value, $request->header($key), "header $key for request $srequest differs");
                    }
                }

                return true;
            };
        }, $this->responses));
    }

    public function addressBookBaseUri()
    {
        return $this->userPrincipal('https://test')
            ->addressbookHome()
            ->resourceTypeAddressBook()
            ->optionsOk('https://test/dav/addressbooks/user@test.com/contacts/');
    }

    public function capabilities()
    {
        return $this->supportedReportSet()
            ->supportedAddressData();
    }

    public function addResponse(string $uri, PromiseInterface $response, ?string $body = null, string $method = 'PROPFIND', ?array $headers = null)
    {
        $this->responses[] = [
            'uri' => $uri,
            'response' => $response,
            'method' => $method,
            'body' => $body,
            'headers' => $headers,
        ];

        return $this;
    }

    public function serviceUrl()
    {
        return $this->addResponse('https://test/.well-known/carddav', Http::response(null, 301, ['Location' => $this->baseUri.'/dav/']), null, 'GET');
    }

    public function nonStandardServiceUrl()
    {
        return $this->addResponse('https://test/.well-known/carddav', Http::response(null, 301, ['Location' => '/dav/']), null, 'PROPFIND');
    }

    public function optionsOk(string $url = 'https://test/dav/')
    {
        return $this->addResponse($url, Http::response(null, 200, ['Dav' => '1, 3, addressbook']), null, 'OPTIONS');
    }

    public function optionsFail()
    {
        return $this->addResponse('https://test/dav/', Http::response(null, 200, ['Dav' => 'bad']), null, 'OPTIONS');
    }

    public function userPrincipal(string $url = 'https://test/dav/')
    {
        return $this->addResponse($url, Http::response($this->multistatusHeader().
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
        '</d:multistatus>'));
    }

    public function userPrincipalEmpty()
    {
        return $this->addResponse('https://test/dav/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:current-user-principal/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function addressbookHome()
    {
        return $this->addResponse('https://test/dav/principals/user@test.com/', Http::response($this->multistatusHeader().
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
        '</d:multistatus>'));
    }

    public function addressbookEmpty()
    {
        return $this->addResponse('https://test/dav/principals/user@test.com/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/principals/user@test.com/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<card:addressbook-home-set/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function resourceTypeAddressBook(string $uri = 'https://test/dav/addressbooks/user@test.com/')
    {
        return $this->addResponse($uri, Http::response($this->multistatusHeader().
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
        '</d:multistatus>'));
    }

    public function resourceTypeHomeOnly()
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:resourcetype/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function resourceTypeEmpty()
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:resourcetype/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function supportedReportSet(array $reportSet = ['card:addressbook-multiget', 'card:addressbook-query', 'd:sync-collection'])
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    '<d:supported-report-set>'.
                    implode('', array_map(function ($report) {
                        return "<d:supported-report><d:report><$report/></d:report></d:supported-report>";
                    }, $reportSet)).
                    '</d:supported-report-set>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function supportedAddressData(array $list = ['card:address-data-type content-type="text/vcard" version="4.0"'])
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
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
        '</d:multistatus>'));
    }

    public function displayName(string $name = 'Test')
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:displayname>$name</d:displayname>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function getSynctoken(string $synctoken = '"test"')
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>/dav/addressbooks/user@test.com/contacts/</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:sync-token>$synctoken</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'));
    }

    public function getSyncCollection(string $synctoken = '"token"', string $etag = '"etag"', string $uuid = 'uuid')
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            "<d:href>https://test/dav/addressbooks/user@test.com/contacts/$uuid</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:getetag>$etag</d:getetag>".
                    '<d:getcontenttype>text/vcard</d:getcontenttype>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        "<d:sync-token>$synctoken</d:sync-token>".
        '</d:multistatus>'), null, 'REPORT');
    }

    public function addressMultiGet($etag, $card, $url)
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response($this->multistatusHeader().
        '<d:response>'.
            '<d:href>https://test/dav/addressbooks/user@test.com/contacts/uuid</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:getetag>$etag</d:getetag>".
                    "<card:address-data>$card</card:address-data>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
        '<card:addressbook-multiget xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
          '<d:prop>'.
            '<d:getetag/>'.
            '<card:address-data content-type="text/vcard" version="4.0"/>'.
          '</d:prop>'.
          "<d:href>$url</d:href>".
        "</card:addressbook-multiget>\n", 'REPORT');
    }

    public static function multistatusHeader()
    {
        return '<d:multistatus xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">';
    }
}
