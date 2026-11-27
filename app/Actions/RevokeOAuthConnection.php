<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\OAuthConnection;
use Illuminate\Support\Str;

/**
 * An app signs itself out with its refresh token. An unknown token, or one of another client,
 * changes nothing, and the caller is not told which it was. A client id that is not a UUID names
 * no client, and is not sent to the database, which would refuse it on PostgreSQL.
 */
class RevokeOAuthConnection
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $refreshToken,
    ) {}

    public function execute(): void
    {
        $connection = $this->heldConnection();

        if ($connection === null) {
            return;
        }

        new EndOAuthConnection(user: $connection->user, connection: $connection)->execute();
    }

    private function heldConnection(): ?OAuthConnection
    {
        if (! Str::isUuid($this->clientId)) {
            return null;
        }

        return OAuthConnection::query()
            ->whereNull('ended_at')
            ->where('client_id', $this->clientId)
            ->where('refresh_token_hash', hash('sha256', $this->refreshToken))
            ->first();
    }
}
