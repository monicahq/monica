<?php

namespace Tests\Helpers;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;

class DavTester extends TestCase
{
    /**
     * @var array
     */
    public $responses;

    /**
     * @var string
     */
    public $baseUri;

    public $container;

    public function __construct(string $baseUri = 'https://test')
    {
        $this->baseUri = $baseUri;
        $this->responses = [];
    }

    public function getClient()
    {
        $this->container = [];
        $history = Middleware::history($this->container);

        $mock = new MockHandler(array_map(function ($response) {
            return $response['response'];
        }, $this->responses));
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        return new Client(['handler' => $handlerStack, 'base_uri' => $this->baseUri]);
    }

    public function assert()
    {
        $this->assertCount(count($this->responses), $this->container, 'the number of response do not match the number of requests');
        foreach ($this->container as $index => $request) {
            $srequest = $request['request']->getMethod().' '.(string) $request['request']->getUri();
            $this->assertEquals($this->responses[$index]['method'], $request['request']->getMethod(), "method for request $srequest differs");
            $this->assertEquals($this->responses[$index]['uri'], (string) $request['request']->getUri(), "uri for request $srequest differs");
            if (isset($this->responses[$index]['body'])) {
                $this->assertEquals($this->responses[$index]['body'], (string) $request['request']->getBody(), "body for request $srequest differs");
            }
            if (isset($this->responses[$index]['headers'])) {
                foreach ($this->responses[$index]['headers'] as $key => $value) {
                    $this->assertArrayHasKey($key, $request['request']->getHeaders(), "header $key for request $srequest is missing");
                    $this->assertEquals($value, $request['request']->getHeaderLine($key), "header $key for request $srequest differs");
                }
            }
        }
    }

    public function addressBookBaseUri()
    {
        return $this->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookHome()
            ->resourceTypeAddressBook();
    }

    public function capabilities()
    {
        return $this->supportedReportSet()
            ->supportedAddressData();
    }

    public function addResponse(string $uri, Response $response, string $body = null, string $method = 'PROPFIND', array $headers = null)
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
        $this->addResponse('https://test/.well-known/carddav', new Response(301, ['Location' => $this->baseUri.'/dav/']), null, 'GET');

        return $this;
    }

    public function nonStandardServiceUrl()
    {
        $this->addResponse('https://test/.well-known/carddav', new Response(301, ['Location' => '/dav/']));

        return $this;
    }

    public function optionsOk()
    {
        $this->addResponse('https://test/dav/', new Response(200, ['Dav' => '1, 3, addressbook']), null, 'OPTIONS');

        return $this;
    }

    public function optionsFail()
    {
        $this->addResponse('https://test/dav/', new Response(200, ['Dav' => 'bad']), null, 'OPTIONS');

        return $this;
    }

    public function userPrincipal()
    {
        $this->addResponse('https://test/dav/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function userPrincipalEmpty()
    {
        $this->addResponse('https://test/dav/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function addressbookHome()
    {
        $this->addResponse('https://test/dav/principals/user@test.com/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function addressbookEmpty()
    {
        $this->addResponse('https://test/dav/principals/user@test.com/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function resourceTypeAddressBook()
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function resourceTypeHomeOnly()
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function resourceTypeEmpty()
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function supportedReportSet(array $reportSet = ['card:addressbook-multiget', 'card:addressbook-query', 'd:sync-collection'])
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function supportedAddressData(array $list = ['card:address-data-type content-type="text/vcard" version="4.0"'])
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function displayName(string $name = 'Test')
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function getSynctoken(string $synctoken = '"test"')
    {
        $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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

        return $this;
    }

    public function getSyncCollection(string $synctoken = 'token', string $etag = '"etag"')
    {
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
        '<d:response>'.
            '<d:href>https://test/dav/addressbooks/user@test.com/contacts/uuid</d:href>'.
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
        return $this->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $this->multistatusHeader().
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
