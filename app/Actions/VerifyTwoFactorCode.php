<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\TextSanitizer;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

/**
 * Check the code somebody typed at the two factor challenge, against the code
 * their authenticator app is showing, and failing that against their recovery
 * codes. A recovery code is spent the moment it works.
 */
class VerifyTwoFactorCode
{
    private string $code;

    public function __construct(
        private readonly User $user,
        string $code,
    ) {
        $this->code = TextSanitizer::plainText($code);
    }

    public function execute(): bool
    {
        if ($this->verifyTimedCode()) {
            return true;
        }

        return $this->verifyRecoveryCode();
    }

    private function verifyTimedCode(): bool
    {
        if ($this->user->two_factor_secret === null) {
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
