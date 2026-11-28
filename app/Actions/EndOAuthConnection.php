<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Disconnect one app: its access stops at once and its refresh token can no longer renew it. The
 * person's other connections and API keys are left alone. An earlier connection stands for every
 * token its assistant holds, so all of them stop, except those another connection follows.
 */
class EndOAuthConnection
{
    public function __construct(
        private readonly User $user,
        private readonly OAuthConnection $connection,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->end();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->connection->user_id !== $this->user->id) {
            throw new ModelNotFoundException('OAuth connection not found');
        }

        if ($this->connection->ended_at !== null) {
            throw new InvalidArgumentException('The app is already disconnected.');
        }
    }

    private function end(): void
    {
        DB::transaction(function (): void {
            $tokenIds = $this->connection->authorization_code_hash === null
                ? $this->tokensOfTheEarlierConnection()
                : [$this->connection->access_token_id];

            Token::query()->whereKey($tokenIds)->update(['revoked' => true]);
            RefreshToken::query()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);

            $this->connection->update(['ended_at' => now()]);
        });
    }

    /** @return list<string> */
    private function tokensOfTheEarlierConnection(): array
    {
        $followedElsewhere = OAuthConnection::query()
            ->whereNull('ended_at')
            ->whereNotNull('access_token_id')
            ->where('user_id', $this->connection->user_id)
            ->where('client_id', $this->connection->client_id)
            ->pluck('access_token_id');

        return Token::query()
            ->where('user_id', $this->connection->user_id)
            ->where('client_id', $this->connection->client_id)
            ->whereNotIn('id', $followedElsewhere)
            ->pluck('id')
            ->all();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::OAuthConnectionEnd,
            parameters: ['name' => $this->connection->name],
        );
    }
}
