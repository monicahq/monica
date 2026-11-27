<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\AccessTokenId;
use App\Models\OAuthConnection;
use Laravel\Passport\Token;

/**
 * Follow an app to the tokens a renewal just gave it. The refresh token it presented becomes the
 * previous one, so presenting it again later can be noticed. An assistant connected before
 * connections were recorded has no fingerprint to match: its earlier connection is only marked as
 * used.
 */
class RenewOAuthConnection
{
    private OAuthConnection $connection;

    public function __construct(
        private readonly string $presentedRefreshToken,
        private readonly string $accessToken,
        private readonly string $refreshToken,
    ) {}

    public function execute(): OAuthConnection
    {
        $this->validate();
        $this->update();

        return $this->connection;
    }

    private function validate(): void
    {
        $connection = OAuthConnection::query()
            ->whereNull('ended_at')
            ->where('refresh_token_hash', hash('sha256', $this->presentedRefreshToken))
            ->first();

        if ($connection !== null) {
            $this->connection = $connection;

            return;
        }

        $token = Token::query()->whereKey((string) AccessTokenId::of($this->accessToken))->firstOrFail();

        $this->connection = OAuthConnection::query()
            ->whereNull('ended_at')
            ->whereNull('authorization_code_hash')
            ->where('user_id', $token->user_id)
            ->where('client_id', $token->client_id)
            ->firstOrFail();
    }

    private function update(): void
    {
        if ($this->connection->authorization_code_hash === null) {
            $this->connection->update(['last_used_at' => now()]);

            return;
        }

        $this->connection->update([
            'access_token_id' => (string) AccessTokenId::of($this->accessToken),
            'previous_refresh_token_hash' => $this->connection->refresh_token_hash,
            'refresh_token_hash' => hash('sha256', $this->refreshToken),
            'last_used_at' => now(),
        ]);
    }
}
