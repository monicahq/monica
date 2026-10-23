<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\RecoveryCodes;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use App\Notifications\SecurityKeyAdded;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * Register any WebAuthn authenticator as a security key. The first second
 * factor on an account turns two factor authentication on, and issues the
 * recovery codes that stand in for a lost key.
 */
class CreateSecurityKey
{
    private string $name;

    private CredentialRecord $record;

    private bool $turnsTwoFactorOn = false;

    /** @param array<string, mixed> $credential */
    public function __construct(
        private readonly User $user,
        string $name,
        private readonly array $credential,
        private readonly string $options,
    ) {
        $this->name = TextSanitizer::plainText($name);
    }

    public function execute(): SecurityKey
    {
        $this->validate();

        $securityKey = DB::transaction(fn (): SecurityKey => $this->create());

        $this->log($securityKey);
        $this->user->notify(new SecurityKeyAdded(name: $securityKey->name));

        return $securityKey;
    }

    private function validate(): void
    {
        try {
            $answer = WebAuthn::fromJson(json_encode($this->credential, JSON_THROW_ON_ERROR), PublicKeyCredential::class);
            $options = WebAuthn::fromJson($this->options, PublicKeyCredentialCreationOptions::class);
            $response = $answer->response;

            if (! $response instanceof AuthenticatorAttestationResponse) {
                throw new InvalidArgumentException('The browser answered with an assertion, not a registration.');
            }

            $this->record = WebAuthn::attestationValidator()->check(
                authenticatorAttestationResponse: $response,
                publicKeyCredentialCreationOptions: $options,
                host: Passkeys::relyingPartyId(),
            );
        } catch (Throwable) {
            $this->refuse(__('The security key could not be added. Please try again.'));
        }

        if (SecurityKey::query()->where('credential_id', $this->credentialId())->exists()) {
            $this->refuse(__('This security key is already added.'));
        }
    }

    private function create(): SecurityKey
    {
        User::query()->whereKey($this->user->id)->lockForUpdate()->first();
        $this->user->refresh();

        $this->turnsTwoFactorOn = ! $this->user->usesTwoFactorAuthentication();

        if ($this->turnsTwoFactorOn) {
            $this->user->two_factor_recovery_codes = RecoveryCodes::generate();
            $this->user->save();
        }

        $securityKey = new SecurityKey;
        $securityKey->user_id = $this->user->id;
        $securityKey->credential_id = $this->credentialId();
        $securityKey->credential = json_decode(WebAuthn::toJson($this->record), true, flags: JSON_THROW_ON_ERROR);
        $securityKey->name = $this->nameFor($securityKey);
        $securityKey->save();

        return $securityKey;
    }

    private function nameFor(SecurityKey $securityKey): string
    {
        if ($this->name !== '' && $this->name !== '_') {
            return $this->name;
        }

        return $securityKey->authenticator ?? __('Security key');
    }

    private function credentialId(): string
    {
        return Base64UrlSafe::encodeUnpadded($this->record->publicKeyCredentialId);
    }

    private function log(SecurityKey $securityKey): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SecurityKeyAdded,
            parameters: ['name' => $securityKey->name],
            loggable: $securityKey,
        )->onQueue('low');

        if (! $this->turnsTwoFactorOn) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TwoFactorEnabled,
        )->onQueue('low');
    }

    private function refuse(string $message): never
    {
        throw ValidationException::withMessages([
            'credential' => $message,
        ]);
    }
}
