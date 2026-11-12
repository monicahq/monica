<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Enums\SsoProviderEnum;
use App\Helpers\PasskeyAvailability;
use App\ViewModels\Auth\LoginViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginViewModelTest extends TestCase
{
    #[Test]
    public function it_offers_passkeys_over_a_secure_connection(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');

        $viewModel = new LoginViewModel;

        $this->assertTrue($viewModel->passkeysAreAvailable());
        $this->assertNull($viewModel->passkeysUnavailableReason());
    }

    private function overHttp(): void
    {
        $this->app['request']->server->set('HTTPS', 'off');
    }

    #[Test]
    public function it_says_why_over_a_connection_that_is_not_secure(): void
    {
        $this->overHttp();

        $viewModel = new LoginViewModel;

        $this->assertFalse($viewModel->passkeysAreAvailable());
        $this->assertEquals(PasskeyAvailability::NOT_SECURE, $viewModel->passkeysUnavailableReason());
    }

    #[Test]
    public function it_exempts_no_host_at_all_not_even_localhost(): void
    {
        $this->overHttp();

        foreach (['localhost', '127.0.0.1', 'monica.test'] as $host) {
            $this->app['request']->headers->set('host', $host);

            $this->assertFalse(new LoginViewModel()->passkeysAreAvailable(), $host);
        }
    }

    #[Test]
    public function it_offers_every_provider_that_is_installed_and_configured(): void
    {
        config()->set('monica.sso_installed', SsoProviderEnum::values());
        $this->credentials('github');
        $this->credentials('google');

        $offered = new LoginViewModel()->providersToOffer();

        $this->assertCount(2, $offered);
        $this->assertEquals('github', $offered[0]['value']);
        $this->assertEquals('GitHub', $offered[0]['name']);
        $this->assertEquals(route('auth.sso.redirect', ['provider' => 'github']), $offered[0]['url']);
        $this->assertEquals('Google', $offered[1]['name']);
    }

    #[Test]
    public function it_offers_nothing_when_no_provider_is_configured(): void
    {
        config()->set('monica.sso_installed', SsoProviderEnum::values());

        $this->assertEquals([], new LoginViewModel()->providersToOffer());
    }

    #[Test]
    public function it_offers_nothing_when_the_package_is_absent(): void
    {
        config()->set('monica.sso_installed', []);
        $this->credentials('github');

        $this->assertEquals([], new LoginViewModel()->providersToOffer());
    }

    private function credentials(string $provider): void
    {
        config([
            "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
            "services.{$provider}.client_secret" => 'that-is-what-she-said',
            "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
        ]);
    }
}
