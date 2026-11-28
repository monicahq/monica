<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\OAuthConnection;

/**
 * An authorization code already exchanged, or a refresh token already replaced, is being presented
 * again: somebody may hold a copy, so the connection it belongs to ends before Passport refuses it
 * (RFC 9700, sections 4.2.4 and 4.14.2). A refresh token coming back within REPLAY_GRACE_IN_SECONDS
 * of its renewal is a second request the app sent at once: Passport refuses it, and the connection
 * goes on.
 */
class EndReplayedOAuthConnection
{
    public function __construct(
        private readonly ?string $authorizationCode = null,
        private readonly ?string $refreshToken = null,
    ) {}

    public function execute(): void
    {
        $connection = $this->replayedConnection();

        if ($connection === null) {
            return;
        }

        new EndOAuthConnection(user: $connection->user, connection: $connection)->execute();
    }

    private function replayedConnection(): ?OAuthConnection
    {
        if ($this->authorizationCode !== null) {
            return OAuthConnection::query()
                ->whereNull('ended_at')
                ->where('authorization_code_hash', hash('sha256', $this->authorizationCode))
                ->first();
        }

        if ($this->refreshToken !== null) {
            return OAuthConnection::query()
                ->whereNull('ended_at')
                ->where('previous_refresh_token_hash', hash('sha256', $this->refreshToken))
                ->where('last_used_at', '<=', now()->subSeconds(OAuthConnection::REPLAY_GRACE_IN_SECONDS))
                ->first();
        }

        return null;
    }
}
