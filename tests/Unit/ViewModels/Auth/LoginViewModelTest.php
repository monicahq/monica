<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

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

    #[Test]
    public function it_says_why_over_a_connection_that_is_not_secure(): void
    {
        $viewModel = new LoginViewModel;

        $this->assertFalse($viewModel->passkeysAreAvailable());
        $this->assertEquals(PasskeyAvailability::NOT_SECURE, $viewModel->passkeysUnavailableReason());
    }

    #[Test]
    public function it_exempts_no_host_at_all_not_even_localhost(): void
    {
        foreach (['localhost', '127.0.0.1', 'erica.test'] as $host) {
            $this->app['request']->headers->set('host', $host);

            $this->assertFalse(new LoginViewModel()->passkeysAreAvailable(), $host);
        }
    }

    #[Test]
    public function it_says_why_when_the_feature_is_switched_off(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');
        config(['webauthn.enable' => false]);

        $viewModel = new LoginViewModel;

        $this->assertFalse($viewModel->passkeysAreAvailable());
        $this->assertEquals(PasskeyAvailability::NOT_ENABLED, $viewModel->passkeysUnavailableReason());
    }
}
