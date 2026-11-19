<?php

namespace Tests\Feature\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\GuzzleMock;
use Tests\TestCase;

class OpenIDConnectProvidersTest extends TestCase
{
    private const ENV = [
        'LOGIN_PROVIDERS' => 'github',
        'OIDC_PROVIDERS' => 'work, My-SSO',
        'OIDC_WORK_NAME' => 'Company SSO',
        'OIDC_WORK_BASE_URL' => 'https://sso.example.com',
        'OIDC_WORK_CLIENT_ID' => 'monica',
        'OIDC_WORK_CLIENT_SECRET' => 'secret',
        'OIDC_MY_SSO_BASE_URL' => 'https://id.example.org',
        'OIDC_MY_SSO_LOGO' => '/img/my-sso.svg',
    ];

    protected function setUp(): void
    {
        foreach (self::ENV as $key => $value) {
            $_ENV[$key] = $_SERVER[$key] = $value;
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::ENV) as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_registers_providers_from_environment(): void
    {
        $this->assertEquals(['github', 'oidc_work', 'oidc_my-sso'], config('auth.login_providers'));

        $this->assertEquals([
            'name' => 'Company SSO',
            'logo' => '/img/auth/openid.svg',
            'base_url' => 'https://sso.example.com',
            'client_id' => 'monica',
            'client_secret' => 'secret',
            'redirect' => '/auth/oidc_work/callback',
        ], config('services.oidc_work'));

        $this->assertEquals([
            'name' => 'My Sso',
            'logo' => '/img/my-sso.svg',
            'base_url' => 'https://id.example.org',
            'redirect' => '/auth/oidc_my-sso/callback',
        ], config('services.oidc_my-sso'));
    }

    #[Test]
    public function it_redirects_to_the_provider(): void
    {
        $mock = new GuzzleMock([
            'https://sso.example.com/.well-known/openid-configuration' => [
                'issuer' => 'https://sso.example.com',
                'authorization_endpoint' => 'https://sso.example.com/authorize',
                'token_endpoint' => 'https://sso.example.com/token',
                'userinfo_endpoint' => 'https://sso.example.com/userinfo',
                'jwks_uri' => 'https://sso.example.com/jwks',
            ],
        ]);
        $this->app['request']->setLaravelSession($this->app['session.store']);
        Socialite::driver('oidc_work')->setHttpClient($mock->getClient());

        $response = $this->get('/auth/oidc_work');

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://sso.example.com/authorize?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertEquals('monica', $query['client_id']);
        $this->assertEquals(config('app.url').'/auth/oidc_work/callback', $query['redirect_uri']);
        $this->assertEquals('code', $query['response_type']);
        $this->assertStringContainsString('openid', $query['scope']);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('nonce', $query);
        $this->assertArrayHasKey('code_challenge', $query);

        $mock->assertResponses();
    }
}
