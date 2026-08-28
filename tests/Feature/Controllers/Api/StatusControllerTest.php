<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

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
    public function it_answers_without_a_token(): void
    {
        $response = $this->json('GET', '/api/v1/status');

        $response->assertOk();
    }
}
