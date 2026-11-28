<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\StartOAuthConnection;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartOAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_remembers_the_phone_with_fingerprints_only(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID));

        $connection = new StartOAuthConnection(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            label: 'Pixel 9',
        )->execute();

        $this->assertSame($jim->id, $connection->user_id);
        $this->assertSame($token->id, $connection->access_token_id);
        $this->assertSame(hash('sha256', 'jello-stapler'), $connection->refresh_token_hash);
        $this->assertSame(hash('sha256', 'pretzel-day'), $connection->authorization_code_hash);
        $this->assertSame('Pixel 9', $connection->label);
        $this->assertSame(['mcp:use'], $connection->scopes);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OAuthConnectionCreation
                && $job->user->id === $jim->id
                && $job->parameters === ['name' => 'Pixel 9'],
        );
    }

    #[Test]
    public function it_cleans_the_name_the_phone_gave_itself(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID));

        $connection = new StartOAuthConnection(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            label: '<b>Jim</b>’s phone',
        )->execute();

        $this->assertSame('Jim’s phone', $connection->label);
    }

    #[Test]
    public function it_keeps_no_name_when_the_phone_gave_none(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID));

        $connection = new StartOAuthConnection(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            label: '   ',
        )->execute();

        $this->assertNull($connection->label);
    }

    #[Test]
    public function it_remembers_an_assistant_the_same_way_under_the_name_of_its_client(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();
        $token = $this->oauthTokenFor(user: $dwight);

        $connection = new StartOAuthConnection(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: null,
            authorizationCode: 'beet-harvest',
        )->execute();

        $this->assertSame($token->client_id, $connection->client_id);
        $this->assertSame(['mcp:use'], $connection->scopes);
        $this->assertNull($connection->refresh_token_hash);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->parameters === ['name' => 'Schrute Farms Assistant'],
        );
    }

    #[Test]
    public function it_refuses_a_token_that_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new StartOAuthConnection(
            accessToken: $this->accessTokenWithId('nobody-at-dunder-mifflin'),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
        )->execute();
    }
}
