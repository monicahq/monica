<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Passport\Token;

/**
 * Withdraw every token an assistant obtained through OAuth, so it loses access at once and has to
 * ask the person again before it can come back.
 */
class DisconnectAssistant
{
    /** @var Collection<int, Token> */
    private Collection $tokens;

    public function __construct(
        private readonly User $user,
        private readonly string $clientId,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->revoke();
        $this->log();
    }

    private function validate(): void
    {
        $this->tokens = $this->user->oauthAccessTokens()
            ->with(['client', 'refreshToken'])
            ->where('client_id', $this->clientId)
            ->where('revoked', false)
            ->get();

        if ($this->tokens->isEmpty()) {
            throw new ModelNotFoundException('Assistant not found');
        }
    }

    private function revoke(): void
    {
        foreach ($this->tokens as $token) {
            $token->revoke();
            $token->refreshToken?->revoke();
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::AssistantDisconnection,
            parameters: ['name' => $this->tokens->first()->client->name],
        )->onQueue('low');
    }
}
