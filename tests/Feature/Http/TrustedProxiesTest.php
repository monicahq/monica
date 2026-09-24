<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        putenv('APP_TRUSTED_PROXIES=*');
        $_ENV['APP_TRUSTED_PROXIES'] = '*';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('APP_TRUSTED_PROXIES');
        unset($_ENV['APP_TRUSTED_PROXIES']);

        parent::tearDown();
    }

    /** @test */
    public function it_redirects_to_home_over_https_when_the_reverse_proxy_is_trusted(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'X-Forwarded-Proto' => 'https',
        ]);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }
}
