<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\AccessTokenId;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\MobileSession;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

/**
 * Remember the phone that just exchanged its authorization code, so it can be renewed, listed and
 * signed out on its own. Only fingerprints of the code and the refresh token are kept.
 */
class StartMobileSession
{
    private Token $token;

    private ?string $cleanDeviceName;

    public function __construct(
        private readonly string $accessToken,
        private readonly string $refreshToken,
        private readonly string $authorizationCode,
        private readonly ?string $deviceName = null,
    ) {}

    public function execute(): MobileSession
    {
        $this->sanitize();
        $this->validate();
        $session = $this->create();
        $this->log($session);

        return $session;
    }

    private function sanitize(): void
    {
        $name = Str::limit(TextSanitizer::plainText((string) $this->deviceName), 255, '');

        $this->cleanDeviceName = $name === '' ? null : $name;
    }

    private function validate(): void
    {
        $this->token = Token::query()
            ->whereKey((string) AccessTokenId::of($this->accessToken))
            ->where('client_id', MobileSession::CLIENT_ID)
            ->whereNotNull('user_id')
            ->firstOrFail();
    }

    private function create(): MobileSession
    {
        return MobileSession::query()->create([
            'user_id' => $this->token->user_id,
            'client_id' => MobileSession::CLIENT_ID,
            'device_name' => $this->cleanDeviceName,
            'access_token_id' => $this->token->id,
            'refresh_token_hash' => hash('sha256', $this->refreshToken),
            'authorization_code_hash' => hash('sha256', $this->authorizationCode),
            'last_used_at' => now(),
        ]);
    }

    private function log(MobileSession $session): void
    {
        LogUserAction::dispatch(
            account: $session->user->account,
            user: $session->user,
            action: UserActionEnum::MobileSessionCreation,
            parameters: ['name' => $session->device_name ?? __('Unnamed phone')],
        );
    }
}
