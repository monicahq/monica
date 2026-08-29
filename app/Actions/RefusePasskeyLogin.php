<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/**
 * Decide whether the person behind a passkey may sign in at all.
 *
 * The credential itself is checked by the package, which knows about signatures
 * and counters. This knows the rules the application adds on top: a deactivated
 * user cannot sign in, and neither can somebody whose account signs in through
 * an identity provider.
 *
 * Every refusal reports the same message, which is the same message a wrong
 * password reports. Telling an attacker the difference between an unregistered
 * credential, a suspended account and one that uses single sign-on hands them a
 * way to find out who has an account here.
 *
 * A soft deleted user arrives as null, because the provider resolves the
 * credential through `retrieveById`, which applies the model's global scopes.
 * That is why there is no separate branch for one.
 */
class RefusePasskeyLogin
{
    public function __construct(
        private readonly ?Authenticatable $candidate,
    ) {}

    public function execute(): User
    {
        if (! $this->candidate instanceof User) {
            $this->fail();
        }

        if (! $this->candidate->is_active || $this->candidate->usesSingleSignOn()) {
            $this->fail();
        }

        return $this->candidate;
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'passkey' => __('These credentials do not match our records.'),
        ]);
    }
}
