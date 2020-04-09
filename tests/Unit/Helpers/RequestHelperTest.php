<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\RequestHelper;
use Illuminate\Support\Facades\Request;

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
        Request::instance()->server->set('REMOTE_ADDR', '123.45.67.89');

        $this->assertEquals(
            'KR',
            RequestHelper::country(null)
        );
    }
}
