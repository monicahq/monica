<?php

namespace Tests\Unit\Services\DavClient\Utils\Dav;

use Tests\TestCase;
use Tests\Helpers\DavTester;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Dav\DavClientException;

class DavClientTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_options()
    {
        $tester = (new DavTester())
            ->addResponse('https://test', Http::response(), null, 'OPTIONS')
            ->addResponse('https://test', Http::response(null, 200, ['Dav' => 'test']), null, 'OPTIONS')
            ->addResponse('https://test', Http::response(null, 200, ['Dav' => ' test ']), null, 'OPTIONS')
            ->fake();
        $client = $tester->client();

        $result = $client->options();
        $this->assertEquals([], $result);

        $result = $client->options();
        $this->assertEquals(['test'], $result);

        $result = $client->options();
        $this->assertEquals(['test'], $result);

        $tester->assert();
    }

    /** @test */
    public function it_get_serviceurl()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->fake();
        $client = $tester->client();

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_get_non_standard_serviceurl()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', Http::response(), null, 'GET')
            ->addResponse('https://test/.well-known/carddav', Http::response(), null, 'GET')
            ->nonStandardServiceUrl()
            ->fake();
        $client = $tester->client();

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_get_non_standard_serviceurl2()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', Http::response(null, 404), null, 'GET')
            ->addResponse('https://test/.well-known/carddav', Http::response(null, 404), null, 'GET')
            ->nonStandardServiceUrl()
            ->fake();
        $client = $tester->client();

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_fail_non_standard()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', Http::response(null, 500), null, 'GET')
            ->fake();
        $client = $tester->client();

        $this->expectException(RequestException::class);
        $client->getServiceUrl();
    }

    /** @test */
    public function it_get_base_uri()
    {
        $tester = (new DavTester())
            ->fake();
        $client = $tester->client();

        $result = $client->path();

        $this->assertEquals('https://test', $result);

        $result = $client->path('xxx');

        $this->assertEquals('https://test/xxx', $result);
    }

    /** @test */
    public function it_set_base_uri()
    {
        $tester = (new DavTester())
            ->fake();
        $client = $tester->client();

        $result = $client->setBaseUri('https://new')
            ->path();

        $this->assertEquals('https://new', $result);
    }

    /** @test */
    public function it_call_propfind()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<d:propfind xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
            "</d:propfind>\n", 'PROPFIND')
        ->fake();

        $client = $tester->client();

        $result = $client->propFind(['{DAV:}test']);

        $tester->assert();
        $this->assertEquals([
            '{DAV:}test' => 'value',
        ], $result);
    }

    /** @test */
    public function it_get_property()
    {
        $tester = (new DavTester())
        ->addResponse('https://test/test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<d:propfind xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
            "</d:propfind>\n", 'PROPFIND')
        ->fake();

        $client = $tester->client();

        $result = $client->getProperty('{DAV:}test', 'https://test/test');

        $tester->assert();
        $this->assertEquals('value', $result);
    }

    /** @test */
    public function it_get_supported_report()
    {
        $tester = (new DavTester('https://test/dav'))
        ->addResponse('https://test/dav', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>/dav</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:supported-report-set>'.
                            '<d:supported-report>'.
                                '<d:report><d:test1/></d:report>'.
                            '</d:supported-report>'.
                            '<d:supported-report>'.
                                '<d:report><d:test2/></d:report>'.
                            '</d:supported-report>'.
                        '</d:supported-report-set>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<d:propfind xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:supported-report-set/>'.
              '</d:prop>'.
            "</d:propfind>\n", 'PROPFIND')
        ->fake();

        $client = $tester->client();

        $result = $client->getSupportedReportSet();

        $tester->assert();
        $this->assertEquals(['{DAV:}test1', '{DAV:}test2'], $result);
    }

    /** @test */
    public function it_sync_collection()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:getetag>"00001-abcd1"</d:getetag>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '<d:sync-token>"00001-abcd1"</d:sync-token>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<d:sync-collection xmlns:d="DAV:">'.
              '<d:sync-token/>'.
              '<d:sync-level>1</d:sync-level>'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
            "</d:sync-collection>\n", 'REPORT')
        ->fake();

        $client = $tester->client();

        $result = $client->syncCollection(['{DAV:}test'], '');

        $tester->assert();
        $this->assertEquals([
            'href' => [
                'properties' => [
                    200 => [
                        '{DAV:}getetag' => '"00001-abcd1"',
                        '{DAV:}test' => 'value',
                    ],
                ],
                'status' => '200',
            ],
            'synctoken' => '"00001-abcd1"',
        ], $result);
    }

    /** @test */
    public function it_sync_collection_with_synctoken()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:getetag>"00001-abcd1"</d:getetag>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '<d:sync-token>"00001-abcd1"</d:sync-token>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<d:sync-collection xmlns:d="DAV:">'.
              '<d:sync-token>"00000-abcd0"</d:sync-token>'.
              '<d:sync-level>1</d:sync-level>'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
            "</d:sync-collection>\n", 'REPORT')
        ->fake();

        $client = $tester->client();

        $result = $client->syncCollection(['{DAV:}test'], '"00000-abcd0"');

        $tester->assert();
        $this->assertEquals([
            'href' => [
                'properties' => [
                    200 => [
                        '{DAV:}getetag' => '"00001-abcd1"',
                        '{DAV:}test' => 'value',
                    ],
                ],
                'status' => '200',
            ],
            'synctoken' => '"00001-abcd1"',
        ], $result);
    }

    /** @test */
    public function it_run_addressbook_multiget_report()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:getetag>"00001-abcd1"</d:getetag>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<card:addressbook-multiget xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
              '<d:href>https://test/contacts/1</d:href>'.
            "</card:addressbook-multiget>\n", 'REPORT')
        ->fake();

        $client = $tester->client();

        $result = $client->addressbookMultiget(['{DAV:}test'], ['https://test/contacts/1']);

        $this->assertEquals([
            'href' => [
                'properties' => [
                    200 => [
                        '{DAV:}getetag' => '"00001-abcd1"',
                        '{DAV:}test' => 'value',
                    ],
                ],
                'status' => '200',
            ],
        ], $result);

        $tester->assert();
    }

    /** @test */
    public function it_run_addressbook_query_report()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:getetag>"00001-abcd1"</d:getetag>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<card:addressbook-query xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:test/>'.
              '</d:prop>'.
            "</card:addressbook-query>\n", 'REPORT')
        ->fake();

        $client = $tester->client();

        $result = $client->addressbookQuery(['{DAV:}test']);

        $tester->assert();
        $this->assertEquals([
            'href' => [
                'properties' => [
                    200 => [
                        '{DAV:}getetag' => '"00001-abcd1"',
                        '{DAV:}test' => 'value',
                    ],
                ],
                'status' => '200',
            ],
        ], $result);
    }

    /** @test */
    public function it_run_proppatch()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>', 207), '<?xml version="1.0"?>'."\n".
            '<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">'."\n".
            ' <d:set>'."\n".
            '  <d:prop>'."\n".
            '   <d:test>value</d:test>'."\n".
            '  </d:prop>'."\n".
            ' </d:set>'."\n".
            "</d:propertyupdate>\n", 'PROPPATCH')
        ->fake();

        $client = $tester->client();

        $result = $client->propPatch(['{DAV:}test' => 'value']);

        $tester->assert();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_run_proppatch_error()
    {
        $tester = (new DavTester())
        ->addResponse('https://test', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:test>x</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 405 OK</d:status>'.
                '</d:propstat>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:excerpt>x</d:excerpt>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 500 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>', 207), '<?xml version="1.0"?>'."\n".
            '<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">'."\n".
            ' <d:set>'."\n".
            '  <d:prop>'."\n".
            '   <d:test>value</d:test>'."\n".
            '   <d:excerpt>value</d:excerpt>'."\n".
            '  </d:prop>'."\n".
            ' </d:set>'."\n".
            "</d:propertyupdate>\n", 'PROPPATCH')
        ->fake();

        $client = $tester->client();

        $this->expectException(DavClientException::class);
        $this->expectExceptionMessage('PROPPATCH failed. The following properties errored: {DAV:}test (405), {DAV:}excerpt (500)');
        $client->propPatch([
            '{DAV:}test' => 'value',
            '{DAV:}excerpt' => 'value',
        ]);
    }
}
