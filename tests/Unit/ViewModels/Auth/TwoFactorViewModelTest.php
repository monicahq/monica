<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Models\Passkey;
use App\Models\SecurityKey;
use App\Models\User;
use App\ViewModels\Auth\TwoFactorViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_somebody_with_the_app_for_its_code(): void
    {
        $viewModel = new TwoFactorViewModel(user: User::factory()->twoFactor()->create());

        $this->assertFalse($viewModel->offersKey());
        $this->assertEquals('Six digit code', $viewModel->codeLabel());
        $this->assertEquals('Open your authenticator app and type the code it is showing.', $viewModel->description());
    }

    #[Test]
    public function it_offers_a_key_and_asks_for_a_recovery_code_otherwise_over_a_secure_connection(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create();

        $viewModel = new TwoFactorViewModel(user: $user);

        $this->assertTrue($viewModel->offersKey());
        $this->assertEquals('Recovery code', $viewModel->codeLabel());
        $this->assertEquals('Use your security key or passkey when your browser asks.', $viewModel->description());
        $this->assertTrue($viewModel->keyPrompt(openOnLoad: true)['openOnLoad']);
    }

    #[Test]
    public function it_offers_the_passkey_of_somebody_with_the_app_and_offers_both_ways(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');
        $user = User::factory()->twoFactor()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $viewModel = new TwoFactorViewModel(user: $user);

        $this->assertTrue($viewModel->offersKey());
        $this->assertEquals('Six digit code', $viewModel->codeLabel());
        $this->assertEquals('Use your security key or passkey when your browser asks, or type the code your authenticator app is showing.', $viewModel->description());
    }

    #[Test]
    public function it_says_why_no_key_is_offered_to_somebody_with_the_app_and_a_key_over_an_insecure_connection(): void
    {
        $user = User::factory()->twoFactor()->create();
        SecurityKey::factory()->for($user)->create();

        $viewModel = new TwoFactorViewModel(user: $user);

        $this->assertFalse($viewModel->offersKey());
        $this->assertEquals('Security keys need a secure connection. Open your authenticator app and type the code it is showing.', $viewModel->description());
    }

    #[Test]
    public function it_points_somebody_with_a_key_alone_at_the_recovery_codes_over_an_insecure_connection(): void
    {
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create();

        $viewModel = new TwoFactorViewModel(user: $user);

        $this->assertFalse($viewModel->offersKey());
        $this->assertEquals('Security keys need a secure connection. Type one of your recovery codes instead.', $viewModel->description());
    }
}
