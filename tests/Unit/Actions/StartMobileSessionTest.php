<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\StartMobileSession;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartMobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_remembers_the_phone_with_fingerprints_only(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(MobileSession::CLIENT_ID));

        $session = new StartMobileSession(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            deviceName: 'Pixel 9',
        )->execute();

        $this->assertSame($jim->id, $session->user_id);
        $this->assertSame($token->id, $session->access_token_id);
        $this->assertSame(hash('sha256', 'jello-stapler'), $session->refresh_token_hash);
        $this->assertSame(hash('sha256', 'pretzel-day'), $session->authorization_code_hash);
        $this->assertSame('Pixel 9', $session->device_name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::MobileSessionCreation
                && $job->user->id === $jim->id
                && $job->parameters === ['name' => 'Pixel 9'],
        );
    }

    #[Test]
    public function it_cleans_the_name_the_phone_gave_itself(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(MobileSession::CLIENT_ID));

        $session = new StartMobileSession(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            deviceName: '<b>Jim</b>’s phone',
        )->execute();

        $this->assertSame('Jim’s phone', $session->device_name);
    }

    #[Test]
    public function it_keeps_no_name_when_the_phone_gave_none(): void
    {
        Queue::fake();

        $jim = User::factory()->create();
        $token = $this->oauthTokenFor(user: $jim, client: Client::query()->findOrFail(MobileSession::CLIENT_ID));

        $session = new StartMobileSession(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
            deviceName: '   ',
        )->execute();

        $this->assertNull($session->device_name);
    }

    #[Test]
    public function it_refuses_a_token_given_to_an_assistant(): void
    {
        $token = $this->oauthTokenFor(user: User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new StartMobileSession(
            accessToken: $this->accessTokenWithId($token->id),
            refreshToken: 'jello-stapler',
            authorizationCode: 'pretzel-day',
        )->execute();
    }
}
