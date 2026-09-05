<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Models\User;
use App\ViewModels\Auth\LoginViewModel;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_passkeys_over_a_secure_connection(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');

        $viewModel = new LoginViewModel;

        $this->assertTrue($viewModel->passkeysAreAvailable());
        $this->assertNull($viewModel->passkeysUnavailableReason());
    }

    /**
     * Say which scheme the request is on rather than inheriting it. In a test
     * the container's request is built from `APP_URL`, so leaving this implicit
     * made these assertions answer to the address in a developer's own `.env`.
     */
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

        foreach (['localhost', '127.0.0.1', 'erica.test'] as $host) {
            $this->app['request']->headers->set('host', $host);

            $this->assertFalse(new LoginViewModel()->passkeysAreAvailable(), $host);
        }
    }

    #[Test]
    public function it_offers_the_seeded_account_to_a_developer(): void
    {
        User::factory()->create(['email' => ExampleAccountSeeder::EMAIL]);

        $viewModel = new LoginViewModel;

        $this->assertEquals(ExampleAccountSeeder::EMAIL, $viewModel->exampleAccountEmail());
        $this->assertEquals('Monica Geller', $viewModel->exampleAccountName());
    }

    /**
     * A login link is configured not to invent a user, so a shortcut pointing at
     * an address nobody seeded throws when it is clicked.
     */
    #[Test]
    public function it_offers_nothing_when_the_example_account_was_never_seeded(): void
    {
        User::factory()->create(['email' => 'gunther@centralperk.example']);

        $this->assertNull(new LoginViewModel()->exampleAccountEmail());
    }
}
