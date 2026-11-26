<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MobileSession;

/**
 * An authorization code already exchanged, or a refresh token already replaced, is being presented
 * again: somebody may hold a copy, so the session it belongs to ends before Passport refuses it
 * (RFC 9700, sections 4.2.4 and 4.14.2). A refresh token coming back within a few seconds of its
 * renewal is a second request the phone sent at once: Passport refuses it, and the session goes on.
 */
class EndReplayedMobileSession
{
    public function __construct(
        private readonly ?string $authorizationCode = null,
        private readonly ?string $refreshToken = null,
    ) {}

    public function execute(): void
    {
        $session = $this->replayedSession();

        if ($session === null) {
            return;
        }

        new EndMobileSession(user: $session->user, session: $session)->execute();
    }

    private function replayedSession(): ?MobileSession
    {
        if ($this->authorizationCode !== null) {
            return MobileSession::query()
                ->whereNull('ended_at')
                ->where('authorization_code_hash', hash('sha256', $this->authorizationCode))
                ->first();
        }

        if ($this->refreshToken !== null) {
            return MobileSession::query()
                ->whereNull('ended_at')
                ->where('previous_refresh_token_hash', hash('sha256', $this->refreshToken))
                ->where('last_used_at', '<=', now()->subSeconds(MobileSession::REPLAY_GRACE_IN_SECONDS))
                ->first();
        }

        return null;
    }
}
