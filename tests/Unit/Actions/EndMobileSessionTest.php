<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EndMobileSession;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndMobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_phone_out(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        [$session, $token] = $this->sessionOn(user: $pam, device: 'Pixel 9');

        new EndMobileSession(user: $pam, session: $session)->execute();

        $this->assertTrue($token->refresh()->revoked);
        $this->assertTrue($token->refreshToken->revoked);
        $this->assertNotNull($session->refresh()->ended_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::MobileSessionEnd
                && $job->user->id === $pam->id
                && $job->parameters === ['name' => 'Pixel 9'],
        );
    }

    #[Test]
    public function it_leaves_the_other_phone_signed_in(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        [$ended] = $this->sessionOn(user: $pam, device: 'Pixel 9');
        [$kept, $keptToken] = $this->sessionOn(user: $pam, device: 'Galaxy S26');

        new EndMobileSession(user: $pam, session: $ended)->execute();

        $this->assertFalse($keptToken->refresh()->revoked);
        $this->assertNull($kept->refresh()->ended_at);
    }

    #[Test]
    public function it_refuses_a_session_of_somebody_else(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        [$session, $token] = $this->sessionOn(user: User::factory()->create(), device: 'Pixel 9');

        $this->expectException(ModelNotFoundException::class);

        try {
            new EndMobileSession(user: $michael, session: $session)->execute();
        } finally {
            $this->assertFalse($token->refresh()->revoked);
        }
    }

    #[Test]
    public function it_refuses_a_session_already_ended(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $session = MobileSession::factory()->ended()->create(['user_id' => $pam->id]);

        $this->expectException(InvalidArgumentException::class);

        new EndMobileSession(user: $pam, session: $session)->execute();
    }

    /** @return array{MobileSession, Token} */
    private function sessionOn(User $user, string $device): array
    {
        $token = $this->oauthTokenFor(user: $user, client: Client::query()->findOrFail(MobileSession::CLIENT_ID));

        $session = MobileSession::factory()->create([
            'user_id' => $user->id,
            'device_name' => $device,
            'access_token_id' => $token->id,
        ]);

        return [$session, $token];
    }
}
