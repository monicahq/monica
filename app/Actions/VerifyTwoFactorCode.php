<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Check the code somebody typed at the two factor challenge, against the code their authenticator
 * app is showing, and failing that against their recovery codes. Five wrong codes in a row lock the
 * challenge for that person for a few minutes, whatever address the guesses come from.
 */
class VerifyTwoFactorCode
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    private string $code;

    public function __construct(
        private readonly User $user,
        string $code,
    ) {
        $this->code = TextSanitizer::plainText($code);
    }

    public function execute(): bool
    {
        $this->validate();

        if ($this->verifyTimedCode() || $this->verifyRecoveryCode()) {
            RateLimiter::clear($this->throttleKey());

            return true;
        }

        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

        return false;
    }

    private function validate(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'code' => __('Too many sign-in attempts. Please try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return "two-factor:{$this->user->id}";
    }

    private function verifyTimedCode(): bool
    {
        if ($this->user->two_factor_secret === null) {
            return false;
        }

        if (! $this->user->usesAuthenticatorApp()) {
            return false;
        }

        return new Google2FA()->verifyKey($this->user->two_factor_secret, $this->code);
    }

    private function verifyRecoveryCode(): bool
    {
        $codes = $this->user->two_factor_recovery_codes;

        if (! is_array($codes) || ! in_array($this->code, $codes, true)) {
            return false;
        }

        $this->user->two_factor_recovery_codes = array_values(array_diff($codes, [$this->code]));
        $this->user->save();

        return true;
    }
}
