<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Mint an API key for somebody, which acts as them and nobody else.
 *
 * What comes back holds both halves of the new key: the plain text value, which
 * is the only time it exists in that form, and the row that was written. Only a
 * hash of the value is kept, so a key somebody fails to copy is lost rather than
 * recoverable, and has to be made again.
 *
 * Callers that only need the value read `->plainTextToken`. The endpoint that
 * hands a key out also has to describe it, its identifier and the moment it was
 * made, and reads `->accessToken` for that rather than going back to the
 * database for a row it has just written.
 */
class CreateApiKey
{
    public function __construct(
        private readonly User $user,
        private readonly string $name,
    ) {}

    public function execute(): NewAccessToken
    {
        $key = $this->user->createToken($this->name);

        $this->log();

        $plainTextToken = explode('|', $key->plainTextToken, 2)[1];

        return new NewAccessToken(
            $key->accessToken,
            $plainTextToken
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ApiKeyCreation,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
