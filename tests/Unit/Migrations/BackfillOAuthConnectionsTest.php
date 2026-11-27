<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillOAuthConnectionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_phone_connections_the_api_scope(): void
    {
        $phone = OAuthConnection::factory()->create(['scopes' => null]);

        $this->migration()->up();

        $this->assertSame([OAuthConnection::API_SCOPE], $phone->refresh()->scopes);
    }

    #[Test]
    public function it_records_one_earlier_connection_per_person_and_assistant_and_no_more_the_second_time(): void
    {
        $pam = User::factory()->create();
        $first = $this->oauthTokenFor(user: $pam);
        $first->forceFill(['created_at' => now()->subDays(5)])->save();
        $second = $this->oauthTokenFor(user: $pam, client: $first->client);
        $this->oauthTokenFor(user: $pam, client: $first->client, revoked: true);

        $this->migration()->up();
        $this->migration()->up();

        $earlier = OAuthConnection::query()->where('user_id', $pam->id)->sole();
        $this->assertSame($first->client_id, $earlier->client_id);
        $this->assertNull($earlier->authorization_code_hash);
        $this->assertSame(['mcp:use'], $earlier->scopes);
        $this->assertEqualsWithDelta($first->fresh()->created_at->timestamp, $earlier->created_at->timestamp, 1);
        $this->assertEqualsWithDelta($second->fresh()->created_at->timestamp, $earlier->last_used_at->timestamp, 1);
    }

    #[Test]
    public function it_leaves_out_the_mobile_app_and_the_tokens_a_connection_already_follows(): void
    {
        $jim = User::factory()->create();
        $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID));
        $followed = $this->oauthTokenFor(user: $jim);
        OAuthConnection::factory()->create(['user_id' => $jim->id, 'client_id' => $followed->client_id, 'access_token_id' => $followed->id]);

        $this->migration()->up();

        $this->assertSame(1, OAuthConnection::query()->where('user_id', $jim->id)->count());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_27_000003_backfill_oauth_connections.php');
    }
}
