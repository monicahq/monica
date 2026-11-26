<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\MobileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reports_that_the_instance_is_up(): void
    {
        config(['monica.version' => '3.1.4', 'monica.instance' => 'scranton.example.com']);

        $response = $this->json('GET', '/api/v1/status');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('version', '3.1.4');
        $response->assertJsonPath('instance', 'scranton.example.com');
    }

    #[Test]
    public function it_tells_the_mobile_apps_how_to_sign_in(): void
    {
        config(['monica.registration_enabled' => false, 'monica.mobile.minimum_android_version' => '1.2.0']);

        $response = $this->json('GET', '/api/v1/status');

        $response->assertJsonPath('mobile.api_version', 1);
        $response->assertJsonPath('mobile.client_id', MobileSession::CLIENT_ID);
        $response->assertJsonPath('mobile.scope', 'api');
        $response->assertJsonPath('mobile.registration_enabled', false);
        $response->assertJsonPath('mobile.minimum_android_version', '1.2.0');
    }

    #[Test]
    public function it_accepts_every_app_when_no_minimum_is_set(): void
    {
        config(['monica.mobile.minimum_android_version' => '']);

        $this->json('GET', '/api/v1/status')->assertJsonPath('mobile.minimum_android_version', null);
    }
}
