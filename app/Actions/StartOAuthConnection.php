<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\AccessTokenId;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\OAuthConnection;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

/**
 * Remember the app that just exchanged its authorization code, whatever the app is, so it can be
 * renewed, listed and disconnected on its own. Only fingerprints of the code and the refresh token
 * are kept. A client that asked for no refresh token is remembered all the same.
 */
class StartOAuthConnection
{
    private Token $token;

    private ?string $cleanLabel;

    public function __construct(
        private readonly string $accessToken,
        private readonly ?string $refreshToken,
        private readonly string $authorizationCode,
        private readonly ?string $label = null,
    ) {}

    public function execute(): OAuthConnection
    {
        $this->sanitize();
        $this->validate();
        $connection = $this->create();
        $this->log($connection);

        return $connection;
    }

    private function sanitize(): void
    {
        $label = Str::limit(TextSanitizer::plainText((string) $this->label), 255, '');

        $this->cleanLabel = $label === '' ? null : $label;
    }

    private function validate(): void
    {
        $this->token = Token::query()
            ->whereKey((string) AccessTokenId::of($this->accessToken))
            ->whereNotNull('user_id')
            ->firstOrFail();
    }

    private function create(): OAuthConnection
    {
        return OAuthConnection::query()->create([
            'user_id' => $this->token->user_id,
            'client_id' => $this->token->client_id,
            'label' => $this->cleanLabel,
            'scopes' => $this->token->scopes,
            'access_token_id' => $this->token->id,
            'refresh_token_hash' => $this->refreshToken === null ? null : hash('sha256', $this->refreshToken),
            'authorization_code_hash' => hash('sha256', $this->authorizationCode),
            'last_used_at' => now(),
        ]);
    }

    private function log(OAuthConnection $connection): void
    {
        LogUserAction::dispatch(
            account: $connection->user->account,
            user: $connection->user,
            action: UserActionEnum::OAuthConnectionCreation,
            parameters: ['name' => $connection->name],
        );
    }
}
