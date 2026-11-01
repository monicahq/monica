<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\SignInProviderDisconnected;
use Illuminate\Validation\ValidationException;

/**
 * Disconnect the provider a user signs in through, which is refused while it is
 * the only thing that still signs them in.
 */
class DisconnectProviderFromUser
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $provider = $this->user->sso_provider;
        $this->disconnect();
        $this->log();
        $this->notify($provider?->brandName() ?? '');

        return $this->user;
    }

    private function validate(): void
    {
        if ($this->user->sso_provider === null) {
            throw ValidationException::withMessages([
                'provider' => __('Your account signs in through no provider.'),
            ]);
        }

        if ($this->user->password !== null || $this->user->hasPasskeys()) {
            return;
        }

        throw ValidationException::withMessages([
            'provider' => __('Set a password or add a passkey first. Disconnecting now would leave you no way to sign in.'),
        ]);
    }

    private function disconnect(): void
    {
        $this->user->sso_provider = null;
        $this->user->sso_id = null;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserSsoDisconnected,
        );
    }

    private function notify(string $provider): void
    {
        $this->user->notify(new SignInProviderDisconnected(provider: $provider));
    }
}
