<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MobileSession;

/**
 * A phone signs itself out with its refresh token. An unknown token, or one of another client,
 * changes nothing, and the caller is not told which it was.
 */
class RevokeMobileSession
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $refreshToken,
    ) {}

    public function execute(): void
    {
        $session = $this->heldSession();

        if ($session === null) {
            return;
        }

        new EndMobileSession(user: $session->user, session: $session)->execute();
    }

    private function heldSession(): ?MobileSession
    {
        return MobileSession::query()
            ->whereNull('ended_at')
            ->where('client_id', $this->clientId)
            ->where('refresh_token_hash', hash('sha256', $this->refreshToken))
            ->first();
    }
}
