<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisconnectAssistant;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisconnectAssistantTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_revokes_every_token_the_assistant_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = $this->oauthTokenFor(user: $user);
        $renewed = $this->oauthTokenFor(user: $user, client: $first->client);

        new DisconnectAssistant(user: $user, clientId: $first->client_id)->execute();

        $this->assertTrue($first->refresh()->revoked);
        $this->assertTrue($renewed->refresh()->revoked);
        $this->assertTrue($first->refreshToken->revoked);
        $this->assertTrue($renewed->refreshToken->revoked);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AssistantDisconnection
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Schrute Farms Assistant'],
        );
    }

    #[Test]
    public function it_leaves_the_other_assistants_connected(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $disconnected = $this->oauthTokenFor(user: $user);
        $kept = $this->oauthTokenFor(user: $user);

        new DisconnectAssistant(user: $user, clientId: $disconnected->client_id)->execute();

        $this->assertFalse($kept->refresh()->revoked);
    }

    #[Test]
    public function it_refuses_an_assistant_somebody_else_connected(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $token = $this->oauthTokenFor(user: $dwight);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DisconnectAssistant(user: $michael, clientId: $token->client_id)->execute();
        } finally {
            $this->assertFalse($token->refresh()->revoked);
        }
    }
}
