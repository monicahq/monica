<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Check the answer a security key or a passkey gave to the two factor
 * challenge, and record that the key was used. Nothing is handed to the passkey
 * package, whose listener would take the answer for a passkey sign-in and send
 * a second email.
 */
class VerifySecondFactorKey
{
    private AuthenticatorAssertionResponse $response;

    private PublicKeyCredentialRequestOptions $requestOptions;

    private string $credentialId;

    /** @param array<string, mixed> $credential */
    public function __construct(
        private readonly User $user,
        private readonly array $credential,
        private readonly string $options,
    ) {}

    public function execute(): SecurityKey|Passkey
    {
        $this->validate();

        $key = DB::transaction(function (): SecurityKey|Passkey {
            $key = $this->find();
            $this->verify($key);

            return $key;
        });

        $this->log($key);

        return $key;
    }

    private function validate(): void
    {
        if (! $this->user->is_active) {
            $this->refuse();
        }

        try {
            $answer = WebAuthn::fromJson(json_encode($this->credential, JSON_THROW_ON_ERROR), PublicKeyCredential::class);
            $this->requestOptions = WebAuthn::fromJson($this->options, PublicKeyCredentialRequestOptions::class);
        } catch (Throwable) {
            $this->refuse();
        }

        if (! $answer->response instanceof AuthenticatorAssertionResponse) {
            $this->refuse();
        }

        $this->response = $answer->response;
        $this->credentialId = Base64UrlSafe::encodeUnpadded($answer->rawId);
    }

    private function find(): SecurityKey|Passkey
    {
        $key = $this->user->securityKeys()->where('credential_id', $this->credentialId)->lockForUpdate()->first()
            ?? $this->user->passkeys()->where('credential_id', $this->credentialId)->lockForUpdate()->first();

        if ($key === null) {
            $this->refuse();
        }

        return $key;
    }

    private function verify(SecurityKey|Passkey $key): void
    {
        try {
            $stored = WebAuthn::fromJson(json_encode($key->credential, JSON_THROW_ON_ERROR), CredentialRecord::class);

            $record = WebAuthn::assertionValidator()->check(
                credentialRecord: $stored,
                authenticatorAssertionResponse: $this->response,
                publicKeyCredentialRequestOptions: $this->requestOptions,
                host: Passkeys::relyingPartyId(),
                userHandle: $stored->userHandle,
            );
        } catch (Throwable) {
            $this->refuse();
        }

        $key->credential = json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR);
        $key->last_used_at = now();
        $key->save();
    }

    private function log(SecurityKey|Passkey $key): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $key instanceof SecurityKey ? UserActionEnum::SecurityKeyUsed : UserActionEnum::PasskeyConfirmed,
            parameters: ['name' => $key->name],
            loggable: $key,
        );
    }

    private function refuse(): never
    {
        throw ValidationException::withMessages([
            'credential' => __('That key was not accepted. Try again, or use another way below.'),
        ]);
    }
}
