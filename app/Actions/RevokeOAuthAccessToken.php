<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Cut off an app whose connection was never written down, from the access token it holds: the token
 * stops at once and its refresh token can no longer renew it. An app with a connection is
 * disconnected with EndOAuthConnection instead, which also ends the connection and writes it to the
 * journal.
 */
class RevokeOAuthAccessToken
{
    public function __construct(
        private readonly string $accessTokenId,
    ) {}

    public function execute(): void
    {
        DB::transaction(function (): void {
            Token::query()->whereKey($this->accessTokenId)->update(['revoked' => true]);
            RefreshToken::query()->where('access_token_id', $this->accessTokenId)->update(['revoked' => true]);
        });
    }
}
