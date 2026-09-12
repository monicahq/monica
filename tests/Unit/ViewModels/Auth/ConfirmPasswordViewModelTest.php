<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Models\Passkey;
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmPasswordViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        request()->server->set('HTTPS', 'on');
    }

    #[Test]
    public function it_offers_the_password_alone(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $viewModel = new ConfirmPasswordViewModel(user: $user);

        $this->assertTrue($viewModel->offersPassword());
        $this->assertFalse($viewModel->offersPasskey());
        $this->assertFalse($viewModel->offersNothing());
    }

    #[Test]
    public function it_offers_the_passkey_alone(): void
    {
        $user = User::factory()->create(['password' => null]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $viewModel = new ConfirmPasswordViewModel(user: $user->refresh());

        $this->assertFalse($viewModel->offersPassword());
        $this->assertTrue($viewModel->offersPasskey());
        $this->assertFalse($viewModel->offersNothing());
    }

    #[Test]
    public function it_offers_both(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $viewModel = new ConfirmPasswordViewModel(user: $user->refresh());

        $this->assertTrue($viewModel->offersPassword());
        $this->assertTrue($viewModel->offersPasskey());
        $this->assertFalse($viewModel->offersNothing());
    }

    #[Test]
    public function it_offers_nothing_when_the_account_holds_neither(): void
    {
        $user = User::factory()->create(['password' => null]);

        $viewModel = new ConfirmPasswordViewModel(user: $user);

        $this->assertFalse($viewModel->offersPassword());
        $this->assertFalse($viewModel->offersPasskey());
        $this->assertTrue($viewModel->offersNothing());
    }
}
