<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils\Dav;

use App\Domains\Contact\DavClient\Services\Utils\Dav\ServiceUrlQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\DavTester;
use Tests\TestCase;

class ServiceUrlQueryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_service_url()
    {
        $tester = (new DavTester('https://test.com'))
            ->addResponse('https://carddav.test.com', Http::response(), null, 'GET')
            ->fake();

        $t = $this;

        $tester->http->macro('getDnsRecord', function (string $hostname, int $type) use ($t): ?Collection {
            $t->assertEquals('srv.test.com', $hostname);
            $t->assertEquals(DNS_SRV, $type);

            return collect([
                [
                    'type' => 'SRV',
                    'pri' => 0,
                    'weight' => 1,
                    'port' => 443,
                    'target' => 'carddav.test.com',
                ],
            ]);
        });

        (new ServiceUrlQuery)
            ->withClient($tester->client())
            ->execute('srv', true, 'https://test.com');

        $tester->assert();
    }

    /** @test */
    public function it_get_service_url_by_weight()
    {
        $tester = (new DavTester('https://test.com'))
            ->addResponse('https://bad.test.com', Http::response(status: 404), method: 'GET')
            ->addResponse('https://carddav.test.com', Http::response(), method: 'GET')
            ->fake();

        $t = $this;

        $tester->http->macro('getDnsRecord', function (string $hostname, int $type) use ($t): ?Collection {
            $t->assertEquals('srv.test.com', $hostname);
            $t->assertEquals(DNS_SRV, $type);

            return collect([
                [
                    'type' => 'SRV',
                    'pri' => 0,
                    'weight' => 10,
                    'port' => 443,
                    'target' => 'bad.test.com',
                ],
                [
                    'type' => 'SRV',
                    'pri' => 0,
                    'weight' => 1,
                    'port' => 443,
                    'target' => 'carddav.test.com',
                ],
            ]);
        });

        (new ServiceUrlQuery)
            ->withClient($tester->client())
            ->execute('srv', true, 'https://test.com');

        $tester->assert();
    }

    /** @test */
    public function it_get_service_url_by_pri()
    {
        $tester = (new DavTester('https://test.com'))
            ->addResponse('https://bad.test.com', Http::response(status: 404), method: 'GET')
            ->addResponse('https://carddav.test.com', Http::response(), method: 'GET')
            ->fake();

        $t = $this;

        $tester->http->macro('getDnsRecord', function (string $hostname, int $type) use ($t): ?Collection {
            $t->assertEquals('srv.test.com', $hostname);
            $t->assertEquals(DNS_SRV, $type);

            return collect([
                [
                    'type' => 'SRV',
                    'pri' => 0,
                    'weight' => 1,
                    'port' => 443,
                    'target' => 'bad.test.com',
                ],
                [
                    'type' => 'SRV',
                    'pri' => 1,
                    'weight' => 1,
                    'port' => 443,
                    'target' => 'carddav.test.com',
                ],
            ]);
        });

        (new ServiceUrlQuery)
            ->withClient($tester->client())
            ->execute('srv', true, 'https://test.com');

        $tester->assert();
    }

    /** @test */
    public function it_get_service_null()
    {
        $tester = (new DavTester('https://test.com'))
            ->fake();

        $t = $this;

        $tester->http->macro('getDnsRecord', function (string $hostname, int $type) use ($t): ?Collection {
            $t->assertEquals('srv.test.com', $hostname);
            $t->assertEquals(DNS_SRV, $type);

            return null;
        });

        (new ServiceUrlQuery)
            ->withClient($tester->client())
            ->execute('srv', true, 'https://test.com');

        $tester->assert();
    }
}
