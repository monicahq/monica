<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\AccessTokenId;
use App\Models\MobileSession;

/**
 * Follow a phone to the tokens a renewal just gave it. The refresh token it presented becomes the
 * previous one, so presenting it again later can be noticed.
 */
class RenewMobileSession
{
    private MobileSession $session;

    public function __construct(
        private readonly string $presentedRefreshToken,
        private readonly string $accessToken,
        private readonly string $refreshToken,
    ) {}

    public function execute(): MobileSession
    {
        $this->validate();
        $this->update();

        return $this->session;
    }

    private function validate(): void
    {
        $this->session = MobileSession::query()
            ->whereNull('ended_at')
            ->where('refresh_token_hash', hash('sha256', $this->presentedRefreshToken))
            ->firstOrFail();
    }

    private function update(): void
    {
        $this->session->update([
            'access_token_id' => (string) AccessTokenId::of($this->accessToken),
            'previous_refresh_token_hash' => $this->session->refresh_token_hash,
            'refresh_token_hash' => hash('sha256', $this->refreshToken),
            'last_used_at' => now(),
        ]);
    }
}
