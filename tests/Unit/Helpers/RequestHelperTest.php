<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Helpers\RequestHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

class RequestHelperTest extends TestCase
{
    /** @test */
    public function get_cf_ip()
    {
        Request::instance()->headers->set('Cf-Connecting-Ip', '1.2.3.4');

        $this->assertEquals(
            '1.2.3.4',
            RequestHelper::ip()
        );
    }

    /** @test */
    public function get_server_ip()
    {
        Request::instance()->server->set('REMOTE_ADDR', '1.2.3.4');

        $this->assertEquals(
            '1.2.3.4',
            RequestHelper::ip()
        );
    }

    /** @test */
    public function get_country_from_cf()
    {
        Request::instance()->headers->set('Cf-Ipcountry', 'XX');

        $this->assertEquals(
            'XX',
            RequestHelper::country('1.2.3.4')
        );
    }

    /** @test */
    public function get_country_from_ip()
    {
        $driver = $this->mock(\Stevebauman\Location\Drivers\Driver::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->with('123.45.67.89')
                ->andReturn(tap(new \Stevebauman\Location\Position(), function ($position) {
                    $position->countryCode = 'TEST';
                }));
        });
        Location::setDriver($driver);

        Request::instance()->server->set('REMOTE_ADDR', '123.45.67.89');

        $this->assertEquals(
            'TEST',
            RequestHelper::country(null)
        );
    }

    /** @test */
    public function get_infos_from_ip()
    {
        config(['location.ipdata.token' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Helpers/ipdata.json'));
        Http::fake([
            'https://api.ipdata.co/*' => Http::response($body, 200),
        ]);

        $this->assertEquals(
            [
                'country' => 'FR',
                'currency' => 'EUR',
                'timezone' => 'Europe/Paris',
            ],
            RequestHelper::infos('test')
        );
    }

    /** @test */
    public function get_infos_from_ip_fail()
    {
        config(['location.ipdata.token' => 'test']);

        Http::fake([
            'https://api.ipdata.co/*' => Http::response(null, 500),
        ]);
        Request::instance()->headers->set('Cf-Ipcountry', 'XX');

        $this->assertEquals(
            [
                'country' => 'XX',
                'currency' => null,
                'timezone' => null,
            ],
            RequestHelper::infos('test')
        );
    }
}
