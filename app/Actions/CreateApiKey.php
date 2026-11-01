<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ApiKeyClientEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Mint an API key for somebody, which acts as them and nobody else. The scopes it is handed become
 * the key's abilities exactly, so a key reaches what it was minted to reach and nothing more. The
 * kind of caller describes the key on the settings screen and in the journal; it restricts nothing.
 */
class CreateApiKey
{
    /** @param  array<int, string>  $scopes */
    public function __construct(
        private readonly User $user,
        private readonly string $name,
        private readonly array $scopes = [],
        private readonly ApiKeyClientEnum $client = ApiKeyClientEnum::Script,
    ) {}

    public function execute(): NewAccessToken
    {
        $key = $this->user->createToken($this->name, $this->scopes);

        $key->accessToken->client = $this->client->value;
        $key->accessToken->save();

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
            parameters: ['name' => $this->name, 'scopes' => $this->scopes, 'client' => $this->client->value],
        );
    }
}
