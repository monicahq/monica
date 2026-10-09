<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://www.cloudflare.com/ips-v4' => Http::response("173.245.48.0/20\n103.21.244.0/22\n"),
            'https://www.cloudflare.com/ips-v6' => Http::response("2400:cb00::/32\n"),
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], -1);

        parent::tearDown();
    }

    #[Test]
    public function it_reads_the_visitor_address_forwarded_by_cloudflare(): void
    {
        config(['laravelcloudflare.enabled' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/up')
            ->assertOk();

        $this->assertSame('203.0.113.7', request()->ip());
    }

    #[Test]
    public function it_ignores_an_address_forwarded_from_outside_cloudflare(): void
    {
        config(['laravelcloudflare.enabled' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/up')
            ->assertOk();

        $this->assertSame('198.51.100.9', request()->ip());
    }

    #[Test]
    public function it_keeps_trusting_the_proxies_the_deployment_names(): void
    {
        config(['laravelcloudflare.enabled' => true, 'trustedproxy.proxies' => '10.0.0.0/8, 192.168.0.0/16']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeader('X-Forwarded-For', '203.0.113.7, 173.245.48.1')
            ->get('/up')
            ->assertOk();

        $this->assertSame('203.0.113.7', request()->ip());
    }

    #[Test]
    public function it_trusts_whichever_proxy_connects_when_the_deployment_says_so(): void
    {
        config(['laravelcloudflare.enabled' => true, 'trustedproxy.proxies' => '*']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/up')
            ->assertOk();

        $this->assertSame('203.0.113.7', request()->ip());
    }

    #[Test]
    public function it_never_contacts_cloudflare_while_switched_off(): void
    {
        config(['laravelcloudflare.enabled' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/up')
            ->assertOk();

        $this->assertSame('173.245.48.1', request()->ip());
        Http::assertNothingSent();
    }
}
