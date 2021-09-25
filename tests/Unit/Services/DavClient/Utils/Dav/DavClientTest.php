<?php

namespace Tests\Unit\Services\DavClient\Utils\Dav;

use Tests\TestCase;
use Tests\Helpers\DavTester;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ServerException;
use App\Services\DavClient\Utils\Dav\DavClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Dav\DavClientException;

class DavClientTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_client()
    {
        $client = new DavClient([
            'base_uri' => 'test',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertInstanceOf(\Sabre\DAV\Xml\Service::class, $client->xml);
    }

    /** @test */
    public function it_fails_if_no_baseuri()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DavClient([]);
    }

    /** @test */
    public function it_accept_guzzle_client()
    {
        $client = new DavClient([], new \GuzzleHttp\Client());
        $this->assertInstanceOf(DavClient::class, $client);
    }

    /** @test */
    public function it_get_options()
    {
        $tester = (new DavTester())
            ->addResponse('https://test', new Response(200, []), null, 'OPTIONS')
            ->addResponse('https://test', new Response(200, ['Dav' => 'test']), null, 'OPTIONS')
            ->addResponse('https://test', new Response(200, ['Dav' => ' test ']), null, 'OPTIONS');
        $client = new DavClient([], $tester->getClient());

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
            ->serviceUrl();
        $client = new DavClient([], $tester->getClient());

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_get_non_standard_serviceurl()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', new Response(200), null, 'GET')
            ->nonStandardServiceUrl();
        $client = new DavClient([], $tester->getClient());

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_get_non_standard_serviceurl2()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', new Response(404), null, 'GET')
            ->nonStandardServiceUrl();
        $client = new DavClient([], $tester->getClient());

        $result = $client->getServiceUrl();

        $tester->assert();
        $this->assertEquals('https://test/dav/', $result);
    }

    /** @test */
    public function it_fail_non_standard()
    {
        $tester = (new DavTester())
            ->addResponse('https://test/.well-known/carddav', new Response(500), null, 'GET');
        $client = new DavClient([], $tester->getClient());

        $this->expectException(ServerException::class);
        $client->getServiceUrl();
    }

    /** @test */
    public function it_get_base_uri()
    {
        $tester = (new DavTester());
        $client = new DavClient([], $tester->getClient());

        $result = $client->getBaseUri();

        $this->assertEquals('https://test', $result);

        $result = $client->getBaseUri('xxx');

        $this->assertEquals('https://test/xxx', $result);
    }

    /** @test */
    public function it_set_base_uri()
    {
        $tester = (new DavTester());
        $client = new DavClient([], $tester->getClient());

        $result = $client->setBaseUri('https://new')
            ->getBaseUri();

        $this->assertEquals('https://new', $result);
    }

    /** @test */
    public function it_call_propfind()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</d:propfind>\n", 'PROPFIND');

        $client = new DavClient([], $tester->getClient());

        $result = $client->propFind('https://test/test', ['{DAV:}test']);

        $tester->assert();
        $this->assertEquals([
            '{DAV:}test' => 'value',
        ], $result);
    }

    /** @test */
    public function it_get_property()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</d:propfind>\n", 'PROPFIND');

        $client = new DavClient([], $tester->getClient());

        $result = $client->getProperty('{DAV:}test', 'https://test/test');

        $tester->assert();
        $this->assertEquals('value', $result);
    }

    /** @test */
    public function it_get_supported_report()
    {
        $tester = (new DavTester('https://test/dav'));

        $tester->addResponse('https://test/dav', new Response(200, [], $tester->multistatusHeader().
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
            "</d:propfind>\n", 'PROPFIND');

        $client = new DavClient([], $tester->getClient());

        $result = $client->getSupportedReportSet();

        $tester->assert();
        $this->assertEquals(['{DAV:}test1', '{DAV:}test2'], $result);
    }

    /** @test */
    public function it_sync_collection()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</d:sync-collection>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        $result = $client->syncCollectionAsync('https://test/test', ['{DAV:}test'], '')
            ->wait();

        $tester->assert();
        $this->assertEquals([
            'href' => [
                200 => [
                    '{DAV:}getetag' => '"00001-abcd1"',
                    '{DAV:}test' => 'value',
                ],
            ],
            'synctoken' => '"00001-abcd1"',
        ], $result);
    }

    /** @test */
    public function it_sync_collection_with_synctoken()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</d:sync-collection>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        $result = $client->syncCollectionAsync('https://test/test', ['{DAV:}test'], '"00000-abcd0"')
            ->wait();

        $tester->assert();
        $this->assertEquals([
            'href' => [
                200 => [
                    '{DAV:}getetag' => '"00001-abcd1"',
                    '{DAV:}test' => 'value',
                ],
            ],
            'synctoken' => '"00001-abcd1"',
        ], $result);
    }

    /** @test */
    public function it_run_addressbook_multiget_report()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</card:addressbook-multiget>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        $result = $client->addressbookMultigetAsync('https://test/test', ['{DAV:}test'], [
            'https://test/contacts/1',
        ])
            ->wait();

        $tester->assert();
        $this->assertEquals([
            'href' => [
                200 => [
                    '{DAV:}getetag' => '"00001-abcd1"',
                    '{DAV:}test' => 'value',
                ],
            ],
        ], $result);
    }

    /** @test */
    public function it_run_addressbook_query_report()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(200, [], $tester->multistatusHeader().
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
            "</card:addressbook-query>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        $result = $client->addressbookQueryAsync('https://test/test', ['{DAV:}test'])
            ->wait();

        $tester->assert();
        $this->assertEquals([
            'href' => [
                200 => [
                    '{DAV:}getetag' => '"00001-abcd1"',
                    '{DAV:}test' => 'value',
                ],
            ],
        ], $result);
    }

    /** @test */
    public function it_run_proppatch()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(207, [], $tester->multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:test>value</d:test>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0"?>'."\n".
            '<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">'."\n".
            ' <d:set>'."\n".
            '  <d:prop>'."\n".
            '   <d:test>value</d:test>'."\n".
            '  </d:prop>'."\n".
            ' </d:set>'."\n".
            "</d:propertyupdate>\n", 'PROPPATCH');

        $client = new DavClient([], $tester->getClient());

        $result = $client->propPatchAsync('https://test/test', ['{DAV:}test' => 'value'])
            ->wait();

        $tester->assert();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_run_proppatch_error()
    {
        $tester = (new DavTester());

        $tester->addResponse('https://test/test', new Response(207, [], $tester->multistatusHeader().
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
            '</d:multistatus>'), '<?xml version="1.0"?>'."\n".
            '<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">'."\n".
            ' <d:set>'."\n".
            '  <d:prop>'."\n".
            '   <d:test>value</d:test>'."\n".
            '   <d:excerpt>value</d:excerpt>'."\n".
            '  </d:prop>'."\n".
            ' </d:set>'."\n".
            "</d:propertyupdate>\n", 'PROPPATCH');

        $client = new DavClient([], $tester->getClient());

        $this->expectException(DavClientException::class);
        $this->expectExceptionMessage('PROPPATCH failed. The following properties errored: {DAV:}test (405), {DAV:}excerpt (500)');
        $result = $client->propPatchAsync('https://test/test', [
            '{DAV:}test' => 'value',
            '{DAV:}excerpt' => 'value',
        ])
            ->wait();
    }
}
