<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Enums\ApiKeyScopeEnum;
use App\Helpers\PasskeyAvailability;
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function usesSingleSignOn(): bool
    {
        return $this->user->usesSingleSignOn();
    }

    public function passwordChangedAt(): ?string
    {
        return $this->user->password_changed_at?->diffForHumans();
    }

    public function usesTwoFactorAuthentication(): bool
    {
        return $this->user->usesTwoFactorAuthentication();
    }

    public function twoFactorConfirmedAt(): ?string
    {
        return $this->user->two_factor_confirmed_at?->diffForHumans();
    }

    public function recoveryCodesRemaining(): int
    {
        return count($this->user->two_factor_recovery_codes ?? []);
    }

    /** @return array<int, array{id: int, name: string, createdAt: string, lastUsedAt: ?string, reachesSensitiveFields: bool}> */
    public function apiKeys(): array
    {
        return $this->user->tokens()
            ->latest()
            ->get()
            ->map(fn (PersonalAccessToken $apiKey): array => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'createdAt' => $apiKey->created_at->diffForHumans(),
                'lastUsedAt' => $apiKey->last_used_at?->diffForHumans(),
                'reachesSensitiveFields' => in_array(ApiKeyScopeEnum::SensitiveFields->value, $apiKey->abilities ?? [], true),
            ])
            ->all();
    }

    public function davAddress(): string
    {
        return route('sabre.dav', ['path' => ''], absolute: true);
    }

    public function apiKeysHeader(): string
    {
        $count = $this->user->tokens()->count();

        if ($count === 0) {
            return __('Active · no API keys');
        }

        return trans_choice('Active · :count API key|Active · :count API keys', $count);
    }

    public function passkeys(): PasskeyListViewModel
    {
        return new PasskeyListViewModel(user: $this->user);
    }

    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    public function usesPasswordlessSignIn(): bool
    {
        return $this->user->usesPasswordlessSignIn();
    }

    public function canRemoveThePassword(): bool
    {
        return ! $this->user->usesSingleSignOn()
            && $this->user->password !== null
            && $this->user->hasPasskeys()
            && $this->passkeysAreAvailable();
    }

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    public function passwordIsConfirmed(): bool
    {
        return $this->secondsBeforeTheConfirmationExpires() > 0;
    }

    /**
     * What is left of the window when the screen is drawn. A page left open outlives its own
     * confirmation, so the screen has to hand the browser a deadline rather than a yes or no.
     */
    public function secondsBeforeTheConfirmationExpires(): int
    {
        $confirmedAt = session('auth.password_confirmed_at');

        if ($confirmedAt === null) {
            return 0;
        }

        return max(0, (int) config('auth.password_timeout') - (time() - (int) $confirmedAt));
    }

    public function user(): User
    {
        return $this->user;
    }
}
