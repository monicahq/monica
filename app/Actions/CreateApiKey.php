<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Mint an API key for somebody, which acts as them and nobody else.
 *
 * What comes back is the key itself, in plain text, and it is the only time it
 * exists in that form: only a hash of it is written down, so a key somebody
 * fails to copy is lost rather than recoverable, and has to be made again.
 *
 * Nothing accepts these keys yet. The screen that hands them out is built
 * before the API that reads them, so the name is all a key carries today.
 */
class CreateApiKey
{
    public function __construct(
        private readonly User $user,
        private readonly string $name,
    ) {}

    public function execute(): string
    {
        $key = $this->user->createToken($this->name)->plainTextToken;

        $this->log();

        return $key;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::ApiKeyCreation,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
