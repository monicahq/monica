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
        $response->assertSessionHas('status');

        $this->assertTrue($token->refresh()->revoked);
    }
}
