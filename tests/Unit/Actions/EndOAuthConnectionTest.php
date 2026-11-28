<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EndOAuthConnection;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndOAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_disconnects_the_app(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        [$connection, $token] = $this->connectionOn(user: $pam, device: 'Pixel 9');

        new EndOAuthConnection(user: $pam, connection: $connection)->execute();

        $this->assertTrue($token->refresh()->revoked);
        $this->assertTrue($token->refreshToken->revoked);
        $this->assertNotNull($connection->refresh()->ended_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OAuthConnectionEnd
                && $job->user->id === $pam->id
                && $job->parameters === ['name' => 'Pixel 9'],
        );
    }

    #[Test]
    public function it_leaves_the_other_connections_alone(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        [$ended] = $this->connectionOn(user: $pam, device: 'Pixel 9');
        [$kept, $keptToken] = $this->connectionOn(user: $pam, device: 'Galaxy S26');

        new EndOAuthConnection(user: $pam, connection: $ended)->execute();

        $this->assertFalse($keptToken->refresh()->revoked);
        $this->assertNull($kept->refresh()->ended_at);
    }

    #[Test]
    public function it_refuses_a_connection_of_somebody_else(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        [$connection, $token] = $this->connectionOn(user: User::factory()->create(), device: 'Pixel 9');

        $this->expectException(ModelNotFoundException::class);

        try {
            new EndOAuthConnection(user: $michael, connection: $connection)->execute();
        } finally {
            $this->assertFalse($token->refresh()->revoked);
        }
    }

    #[Test]
    public function it_refuses_a_connection_already_ended(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $connection = OAuthConnection::factory()->ended()->create(['user_id' => $pam->id]);

        $this->expectException(InvalidArgumentException::class);

        new EndOAuthConnection(user: $pam, connection: $connection)->execute();
    }

    #[Test]
    public function it_disconnects_an_assistant_under_the_name_of_its_client(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();
        $token = $this->oauthTokenFor(user: $dwight);
        $connection = OAuthConnection::factory()->create([
            'user_id' => $dwight->id,
            'client_id' => $token->client_id,
            'label' => null,
            'access_token_id' => $token->id,
        ]);

        new EndOAuthConnection(user: $dwight, connection: $connection)->execute();

        $this->assertTrue($token->refresh()->revoked);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Schrute Farms Assistant'],
        );
    }

    #[Test]
    public function it_stops_every_token_of_an_earlier_connection_but_those_another_connection_follows(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();
        $first = $this->oauthTokenFor(user: $dwight);
        $assistant = $first->client;
        $second = $this->oauthTokenFor(user: $dwight, client: $assistant);
        $followed = $this->oauthTokenFor(user: $dwight, client: $assistant);

        $earlier = OAuthConnection::factory()->create([
            'user_id' => $dwight->id,
            'client_id' => $assistant->id,
            'label' => null,
            'access_token_id' => null,
            'refresh_token_hash' => null,
            'authorization_code_hash' => null,
        ]);
        OAuthConnection::factory()->create([
            'user_id' => $dwight->id,
            'client_id' => $assistant->id,
            'access_token_id' => $followed->id,
        ]);

        new EndOAuthConnection(user: $dwight, connection: $earlier)->execute();

        $this->assertTrue($first->refresh()->revoked);
        $this->assertTrue($second->refresh()->revoked);
        $this->assertTrue($second->refreshToken->revoked);
        $this->assertFalse($followed->refresh()->revoked);
        $this->assertNotNull($earlier->refresh()->ended_at);
    }

    /** @return array{OAuthConnection, Token} */
    private function connectionOn(User $user, string $device): array
    {
        $token = $this->oauthTokenFor(user: $user, client: Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID));

        $connection = OAuthConnection::factory()->create([
            'user_id' => $user->id,
            'label' => $device,
            'access_token_id' => $token->id,
        ]);

        return [$connection, $token];
    }
}
