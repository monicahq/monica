<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Mint an API key for somebody, which acts as them and nobody else.
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
