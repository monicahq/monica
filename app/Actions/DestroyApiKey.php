<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Revoke an API key, which stops working the moment this runs.
 */
class DestroyApiKey
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly int $apiKeyId,
    ) {}

    public function execute(): void
    {
        $apiKey = $this->user->tokens()->findOrFail($this->apiKeyId);

        $this->name = $apiKey->name;
        $apiKey->delete();

        $this->log();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ApiKeyDeletion,
            parameters: ['name' => $this->name],
        );
    }
}
