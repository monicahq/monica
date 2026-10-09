<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordlessRecallTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_recognises_a_remembered_browser_whose_account_has_no_password(): void
    {
        $user = User::factory()->create(['password' => null, 'remember_token' => 'scranton-branch']);

        $this->withCookie(Auth::guard('web')->getRecallerName(), $this->recallerFor($user, 'scranton-branch', ''))
            ->get(route('vaults.index'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_a_remembered_browser_holding_the_wrong_token(): void
    {
        $user = User::factory()->create(['password' => null, 'remember_token' => 'scranton-branch']);

        $this->withCookie(Auth::guard('web')->getRecallerName(), $this->recallerFor($user, 'stamford-branch', ''))
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_remembered_browser_once_the_password_it_knew_is_removed(): void
    {
        $user = User::factory()->create(['remember_token' => 'scranton-branch']);
        $cookie = $this->recallerFor($user, 'scranton-branch', $user->password);

        $user->forceFill(['password' => null])->save();

        $this->withCookie(Auth::guard('web')->getRecallerName(), $cookie)
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    private function recallerFor(User $user, string $token, string $password): string
    {
        return "{$user->id}|{$token}|".Auth::guard('web')->hashPasswordForCookie($password);
    }
}
