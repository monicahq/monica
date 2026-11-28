<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthConnectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_disconnects_an_app(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->create(['user_id' => $user->id, 'label' => 'Pixel 9']);

        $response = $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.oauthConnections.destroy', $connection->id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Pixel 9 is disconnected.');
        $this->assertNotNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_does_not_find_the_connection_of_somebody_else(): void
    {
        $connection = OAuthConnection::factory()->create();

        $this->withConfirmedPassword()->actingAs(User::factory()->create())
            ->delete(route('settings.oauthConnections.destroy', $connection->id))
            ->assertNotFound();

        $this->assertNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_does_not_find_a_connection_already_ended(): void
    {
        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->ended()->create(['user_id' => $user->id]);

        $this->withConfirmedPassword()->actingAs($user)
            ->delete(route('settings.oauthConnections.destroy', $connection->id))
            ->assertNotFound();
    }
}
