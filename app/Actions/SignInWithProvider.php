<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\AbstractUser as ProviderUser;

/**
 * Sign somebody in with what a provider says about them, creating their account
 * when the instance has never seen them. The provider's own identifier is what
 * a returning person is recognised by, so changing the address at the provider
 * never loses the account and never reaches somebody else's.
 */
class SignInWithProvider
{
    private User $user;

    public function __construct(
        private readonly SsoProviderEnum $provider,
        private readonly ProviderUser $providerUser,
        private readonly ?string $ip = null,
        private readonly ?string $timezone = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): User
    {
        $this->resolve();
        $this->stamp();
        $this->notify();
        $this->log();

        return $this->user;
    }

    private function resolve(): void
    {
        $connected = User::query()
            ->where('sso_provider', $this->provider)
            ->where('sso_id', $this->identifier())
            ->first();

        if ($connected !== null) {
            $this->refuseWhenInactive($connected);
            $this->user = $connected;

            return;
        }

        $this->refuseWithoutEmail();
        $this->refuseWhenTheAddressIsTaken();

        $this->user = new CreateAccountFromProvider(
            provider: $this->provider,
            ssoId: $this->identifier(),
            email: $this->email(),
            firstName: $this->firstName(),
            lastName: $this->lastName(),
            timezone: $this->timezone,
            locale: $this->locale,
        )->execute();
    }

    private function refuseWhenInactive(User $user): void
    {
        if ($user->is_active) {
            return;
        }

        throw ValidationException::withMessages([
            'provider' => __('These credentials do not match our records.'),
        ]);
    }

    private function refuseWithoutEmail(): void
    {
        if (filled($this->providerUser->getEmail())) {
            return;
        }

        throw ValidationException::withMessages([
            'provider' => __(':provider did not give us an email address for you, so we cannot sign you in.', [
                'provider' => $this->provider->brandName(),
            ]),
        ]);
    }

    private function refuseWhenTheAddressIsTaken(): void
    {
        $taken = User::query()->where('email', $this->email())->exists();

        if (! $taken) {
            return;
        }

        throw ValidationException::withMessages([
            'provider' => __('An account already uses that email address. Sign in the way you usually do, then connect :provider from your security settings.', [
                'provider' => $this->provider->brandName(),
            ]),
        ]);
    }

    private function identifier(): string
    {
        return (string) $this->providerUser->getId();
    }

    private function email(): string
    {
        return mb_strtolower((string) $this->providerUser->getEmail());
    }

    private function firstName(): string
    {
        $name = trim((string) ($this->providerUser->getName() ?? $this->providerUser->getNickname() ?? ''));

        return $name === '' ? $this->email() : str($name)->before(' ')->toString();
    }

    private function lastName(): string
    {
        $name = trim((string) ($this->providerUser->getName() ?? ''));

        return str_contains($name, ' ') ? str($name)->after(' ')->toString() : '';
    }

    private function stamp(): void
    {
        $this->user->last_login_at = now();
        $this->user->save();

        CheckLastLogin::dispatch(
            user: $this->user,
            ip: $this->ip ?? '',
        )->onQueue('low');
    }

    private function notify(): void
    {
        $this->user->notify(new NewLoginDetected(ip: $this->ip ?? ''));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserLogin,
        )->onQueue('low');
    }
}
