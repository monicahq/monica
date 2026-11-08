<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Helpers\SsoProviderAvailability;
use App\Jobs\LogUserAction;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Notifications\SignInProviderDisconnected;
use Illuminate\Validation\ValidationException;

/**
 * Disconnect one provider a user signs in through, which is refused while it is
 * the only thing that still signs them in.
 */
class DisconnectProviderFromUser
{
    private ProviderConnection $connection;

    public function __construct(
        private readonly User $user,
        private readonly SsoProviderEnum $provider,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->disconnect();
        $this->log();
        $this->notify();

        return $this->user;
    }

    private function validate(): void
    {
        $connection = $this->user->providerConnections()
            ->where('provider', $this->provider)
            ->first();

        if ($connection === null) {
            throw ValidationException::withMessages([
                'provider' => __('Your account signs in through no provider.'),
            ]);
        }

        $this->connection = $connection;

        if ($this->user->password !== null || $this->user->hasPasskeys()) {
            return;
        }

        $anotherProviderSignsIn = $this->user->providerConnections()
            ->whereKeyNot($this->connection->id)
            ->get()
            ->contains(fn (ProviderConnection $other): bool => SsoProviderAvailability::isAvailable($other->provider));

        if ($anotherProviderSignsIn) {
            return;
        }

        throw ValidationException::withMessages([
            'provider' => __('Set a password or add a passkey first. Disconnecting now would leave you no way to sign in.'),
        ]);
    }

    private function disconnect(): void
    {
        $this->connection->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserSsoDisconnected,
        );
    }

    private function notify(): void
    {
        $this->user->notify(new SignInProviderDisconnected(provider: $this->provider->brandName()));
    }
}
