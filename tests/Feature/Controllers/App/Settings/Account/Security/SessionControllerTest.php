<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_another_device_out(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $sessionId = $this->insertSession(user: $user);

        $response = $this->actingAs($user)
            ->withConfirmedPassword()
            ->delete(route('settings.sessions.destroy', $sessionId));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'The device is signed out.');
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    #[Test]
    public function it_asks_for_the_password_first(): void
    {
        $user = User::factory()->create();
        $sessionId = $this->insertSession(user: $user);

        $response = $this->actingAs($user)->delete(route('settings.sessions.destroy', $sessionId));

        $response->assertRedirect(route('auth.confirmPassword.new'));
        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);
    }

    #[Test]
    public function it_refuses_the_device_in_hand(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $sessionId = $this->insertSession(user: $user);

        $response = $this->actingAs($user)
            ->withCookie(config('session.cookie'), $sessionId)
            ->withConfirmedPassword()
            ->delete(route('settings.sessions.destroy', $sessionId));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('error', 'This is the device you are on. Sign out to end it.');
        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);
    }

    #[Test]
    public function it_answers_not_found_for_the_session_of_another_user(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $sessionId = $this->insertSession(user: $dwight);

        $response = $this->actingAs($michael)
            ->withConfirmedPassword()
            ->delete(route('settings.sessions.destroy', $sessionId));

        $response->assertNotFound();
        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);
    }

    private function insertSession(User $user): string
    {
        $sessionId = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);

        return $sessionId;
    }
}
