<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\ExplainAPasskeyBoundElsewhere;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Uid\NilUuid;
use Tests\TestCase;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\Event\AuthenticatorAssertionResponseValidationFailedEvent;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

class ExplainAPasskeyBoundElsewhereTest extends TestCase
{
    private function eventFor(Throwable $throwable): AuthenticatorAssertionResponseValidationFailedEvent
    {
        return new AuthenticatorAssertionResponseValidationFailedEvent(
            credentialRecord: new CredentialRecord(
                publicKeyCredentialId: 'a-credential',
                type: 'public-key',
                transports: ['internal'],
                attestationType: 'none',
                trustPath: EmptyTrustPath::create(),
                aaguid: new NilUuid,
                credentialPublicKey: 'a-public-key',
                userHandle: '1',
                counter: 1,
            ),
            authenticatorAssertionResponse: Mockery::mock(AuthenticatorAssertionResponse::class),
            publicKeyCredentialRequestOptions: PublicKeyCredentialRequestOptions::create('a-challenge'),
            host: 'erica.test',
            userHandle: '1',
            throwable: $throwable,
        );
    }

    /**
     * The message this listener matches on is the library's, not ours. If a
     * library upgrade rewords it, this fails, which is the point: the hint would
     * otherwise be lost without anybody noticing.
     */
    #[Test]
    public function the_library_still_words_the_mismatch_the_way_this_listener_expects(): void
    {
        $this->assertEquals(
            'rpId hash mismatch.',
            AuthenticatorResponseVerificationException::create('rpId hash mismatch.')->getMessage(),
        );
    }

    #[Test]
    public function it_explains_a_passkey_made_for_another_address(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This passkey was made for a different address');

        new ExplainAPasskeyBoundElsewhere()->handle(
            $this->eventFor(AuthenticatorResponseVerificationException::create('rpId hash mismatch.')),
        );
    }

    #[Test]
    public function it_stays_out_of_the_way_of_an_ordinary_failed_assertion(): void
    {
        new ExplainAPasskeyBoundElsewhere()->handle(
            $this->eventFor(new RuntimeException('a wrong signature')),
        );

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function it_stays_out_of_the_way_of_another_ceremony_check(): void
    {
        new ExplainAPasskeyBoundElsewhere()->handle(
            $this->eventFor(AuthenticatorResponseVerificationException::create('Invalid challenge.')),
        );

        $this->expectNotToPerformAssertions();
    }
}
