<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Listeners\NotifyOfPasskeyAnomaly;
use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Uid\NilUuid;
use Tests\TestCase;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\Event\AuthenticatorAssertionResponseValidationFailedEvent;
use Webauthn\Exception\CounterException;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

class NotifyOfPasskeyAnomalyTest extends TestCase
{
    use RefreshDatabase;

    private function eventFor(string $credentialId, Throwable $throwable): AuthenticatorAssertionResponseValidationFailedEvent
    {
        return new AuthenticatorAssertionResponseValidationFailedEvent(
            credentialRecord: new CredentialRecord(
                publicKeyCredentialId: $credentialId,
                type: 'public-key',
                transports: ['internal'],
                attestationType: 'none',
                trustPath: EmptyTrustPath::create(),
                aaguid: new NilUuid,
                credentialPublicKey: 'a-public-key',
                userHandle: '1',
                counter: 7,
            ),
            authenticatorAssertionResponse: Mockery::mock(AuthenticatorAssertionResponse::class),
            publicKeyCredentialRequestOptions: PublicKeyCredentialRequestOptions::create('a-challenge'),
            host: 'erica.test',
            userHandle: '1',
            throwable: $throwable,
        );
    }

    #[Test]
    public function it_tells_the_owner_when_a_counter_went_backwards(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'a-cloned-credential']);

        new NotifyOfPasskeyAnomaly()->handle(
            $this->eventFor('a-cloned-credential', CounterException::create(9, 3, 'Invalid counter.')),
        );

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::PasskeyCounterAnomaly,
        );
    }

    #[Test]
    public function it_says_nothing_about_an_ordinary_failed_assertion(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'a-credential']);

        new NotifyOfPasskeyAnomaly()->handle(
            $this->eventFor('a-credential', new RuntimeException('a wrong signature')),
        );

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_says_nothing_about_a_credential_it_does_not_hold(): void
    {
        Queue::fake();

        new NotifyOfPasskeyAnomaly()->handle(
            $this->eventFor('nobody-owns-this', CounterException::create(9, 3, 'Invalid counter.')),
        );

        Queue::assertNothingPushed();
    }
}
