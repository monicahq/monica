<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/**
 * Decide whether the person behind a passkey may sign in at all.
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

    public static function refuse(): never
    {
        throw ValidationException::withMessages([
            'passkey' => __('These credentials do not match our records.'),
        ]);
    }

    private function fail(): never
    {
        self::refuse();
    }
}
