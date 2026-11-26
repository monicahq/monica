<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_a_phone_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $session = MobileSession::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.mobileSessions.destroy', $session->id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status');
        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_does_not_find_the_phone_of_somebody_else(): void
    {
        $session = MobileSession::factory()->create();

        $this->withConfirmedPassword()->actingAs(User::factory()->create())
            ->delete(route('settings.mobileSessions.destroy', $session->id))
            ->assertNotFound();

        $this->assertNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_does_not_find_a_phone_already_signed_out(): void
    {
        $user = User::factory()->create();
        $session = MobileSession::factory()->ended()->create(['user_id' => $user->id]);

        $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.mobileSessions.destroy', $session->id))
            ->assertNotFound();
    }
}
