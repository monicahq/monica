<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Connect a provider to somebody already signed in. It adds a way in and takes
 * none away: a password, a passkey or another provider keeps working.
 */
class ConnectProviderToUser
{
    public function __construct(
        private readonly User $user,
        private readonly SsoProviderEnum $provider,
        private readonly string $ssoId,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->connect();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        $alreadyConnected = $this->user->providerConnections()
            ->where('provider', $this->provider)
            ->exists();

        if ($alreadyConnected) {
            throw ValidationException::withMessages([
                'provider' => __('Your account already signs in through :provider. Disconnect it first.', [
                    'provider' => $this->provider->brandName(),
                ]),
            ]);
        }

        $taken = ProviderConnection::query()
            ->where('provider', $this->provider)
            ->where('sso_id', $this->ssoId)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'provider' => __('That :provider account is already connected to somebody else here.', [
                    'provider' => $this->provider->brandName(),
                ]),
            ]);
        }
    }

    private function connect(): void
    {
        $this->user->providerConnections()->create([
            'provider' => $this->provider,
            'sso_id' => $this->ssoId,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserSsoConnected,
        );
    }
}
