<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RevokeOAuthConnection;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeOAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ends_the_connection_of_the_phone_holding_the_refresh_token(): void
    {
        Queue::fake();

        $connection = OAuthConnection::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new RevokeOAuthConnection(clientId: OAuthConnection::MOBILE_CLIENT_ID, refreshToken: 'jello-stapler')->execute();

        $this->assertNotNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_ends_the_connection_of_an_assistant_and_nothing_else(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant']);
        $connection = OAuthConnection::factory()->create([
            'user_id' => $dwight->id,
            'client_id' => $assistant->id,
            'refresh_token_hash' => hash('sha256', 'beet-harvest'),
        ]);
        $phone = OAuthConnection::factory()->create(['user_id' => $dwight->id]);

        new RevokeOAuthConnection(clientId: (string) $assistant->id, refreshToken: 'beet-harvest')->execute();

        $this->assertNotNull($connection->refresh()->ended_at);
        $this->assertNull($phone->refresh()->ended_at);
    }

    #[Test]
    public function it_changes_nothing_for_a_token_nobody_holds(): void
    {
        $connection = OAuthConnection::factory()->create();

        new RevokeOAuthConnection(clientId: OAuthConnection::MOBILE_CLIENT_ID, refreshToken: 'beets')->execute();

        $this->assertNull($connection->refresh()->ended_at);
    }

    #[Test]
    public function it_changes_nothing_when_another_client_names_the_token(): void
    {
        $connection = OAuthConnection::factory()->create(['refresh_token_hash' => hash('sha256', 'jello-stapler')]);

        new RevokeOAuthConnection(clientId: 'schrute-farms', refreshToken: 'jello-stapler')->execute();

        $this->assertNull($connection->refresh()->ended_at);
    }
}
