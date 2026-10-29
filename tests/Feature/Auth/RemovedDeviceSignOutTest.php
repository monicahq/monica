<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\DestroyOtherSessions;
use App\Actions\DestroySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemovedDeviceSignOutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_a_remembered_device_to_sign_in_once_a_session_is_removed(): void
    {
        Queue::fake();
        $user = User::factory()->create(['remember_token' => 'scranton-branch']);
        $recaller = "{$user->id}|scranton-branch|".Auth::guard('web')->hashPasswordForCookie($user->password);
        $this->insertSession(id: 'stamford', user: $user);

        $this->withCookie(Auth::guard('web')->getRecallerName(), $recaller)
            ->get(route('vaults.index'))
            ->assertOk();

        new DestroySession(user: $user, sessionId: 'stamford', currentSessionId: 'scranton')->execute();
        $this->flushSession();
        Auth::forgetGuards();

        $this->withCookie(Auth::guard('web')->getRecallerName(), $recaller)
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    #[Test]
    public function it_lets_a_live_session_carry_on_once_another_is_removed(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $this->insertSession(id: 'stamford', user: $user);

        new DestroySession(user: $user, sessionId: 'stamford', currentSessionId: 'scranton')->execute();

        $this->withSession([
            Auth::guard('web')->getName() => $user->id,
            'password_hash_web' => Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()),
        ])
            ->get(route('vaults.index'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_asks_every_other_device_to_sign_in_once_they_are_all_signed_out(): void
    {
        Queue::fake();
        $user = User::factory()->create(['password' => Hash::make('worlds-best-boss'), 'remember_token' => 'scranton-branch']);
        $recaller = "{$user->id}|scranton-branch|".Auth::guard('web')->hashPasswordForCookie($user->password);

        $this->actingAs($user);
        new DestroyOtherSessions(user: $user, password: 'worlds-best-boss', currentSessionId: 'scranton')->execute();
        $this->flushSession();
        Auth::forgetGuards();

        $this->withCookie(Auth::guard('web')->getRecallerName(), $recaller)
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));

        $this->assertGuest();
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => null,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
