<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\ResetPassword;
use App\Actions\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtherDeviceSignOutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_out_a_device_still_holding_the_password_that_was_changed(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();
        $session = $this->sessionOpenedWith($user);

        new UpdateUserPassword(user: $user, password: 'schrute-farms-2005')->execute();

        $this->withSession($session)
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    #[Test]
    public function it_signs_out_a_device_still_holding_the_password_that_was_reset(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create(['email' => 'dwight@dundermifflin.com']);
        $session = $this->sessionOpenedWith($user);

        new ResetPassword(
            email: 'dwight@dundermifflin.com',
            token: Password::createToken($user),
            password: 'schrute-farms-2005',
        )->execute();

        $this->withSession($session)
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    #[Test]
    public function it_lets_a_device_holding_the_current_password_carry_on(): void
    {
        $user = User::factory()->create();

        $this->withSession($this->sessionOpenedWith($user))
            ->get(route('vaults.index'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    /** @return array<string, int|string> */
    private function sessionOpenedWith(User $user): array
    {
        return [
            Auth::guard('web')->getName() => $user->id,
            'password_hash_web' => Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()),
        ];
    }
}
