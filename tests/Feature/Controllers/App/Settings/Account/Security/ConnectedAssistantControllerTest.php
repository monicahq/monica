<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectedAssistantControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_disconnects_an_assistant(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $this->oauthTokenFor(user: $user);

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.connectedAssistants.destroy', $token->client_id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'The assistant is disconnected.');

        $this->assertTrue($token->refresh()->revoked);
    }

    #[Test]
    public function it_asks_for_the_password_before_disconnecting(): void
    {
        $user = User::factory()->create();
        $token = $this->oauthTokenFor(user: $user);

        $response = $this->actingAs($user)
            ->delete(route('settings.connectedAssistants.destroy', $token->client_id));

        $response->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertFalse($token->refresh()->revoked);
    }

    #[Test]
    public function it_lists_the_connected_assistants_on_the_security_screen(): void
    {
        $user = User::factory()->create();
        $token = $this->oauthTokenFor(user: $user);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertOk();
        $response->assertSee('Connected assistants');
        $response->assertSee('Schrute Farms Assistant');
        $response->assertSee('action="'.route('settings.connectedAssistants.destroy', $token->client_id).'"', false);
    }
}
