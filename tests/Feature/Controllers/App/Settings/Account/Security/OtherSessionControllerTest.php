<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtherSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_every_other_device_out(): void
    {
        Queue::fake();
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => Hash::make('worlds-best-boss')]);
        $sessionId = $this->insertSession(user: $user);

        $response = $this->actingAs($user)->delete(route('settings.otherSessions.destroy'), [
            'password' => 'worlds-best-boss',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Every other device is signed out.');
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    #[Test]
    public function it_refuses_a_wrong_password(): void
    {
        Queue::fake();
        $user = User::factory()->create(['password' => Hash::make('worlds-best-boss')]);
        $sessionId = $this->insertSession(user: $user);

        $response = $this->actingAs($user)->delete(route('settings.otherSessions.destroy'), [
            'password' => 'bears-beets-battlestar',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('error', 'That is not your password. No device was signed out.');
        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);
    }

    #[Test]
    public function it_requires_the_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('settings.otherSessions.destroy'));

        $response->assertSessionHasErrors('password');
    }

    private function insertSession(User $user): string
    {
        $sessionId = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => null,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);

        return $sessionId;
    }
}
