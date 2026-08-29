<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Helpers\PasskeyAvailability;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * What the security screen shows: what somebody can change about the way they
 * sign in, and the shell around it.
 */
class SecurityViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * Whether the account signs in through an identity provider, in which case
     * there is no password to change and the screen says so instead of showing
     * fields that lead nowhere.
     */
    public function usesSingleSignOn(): bool
    {
        return $this->user->usesSingleSignOn();
    }

    /**
     * How long ago the password was last changed or set, in words, or null when
     * it never was, so the screen can leave the line out entirely.
     */
    public function passwordChangedAt(): ?string
    {
        return $this->user->password_changed_at?->diffForHumans();
    }

    public function usesTwoFactorAuthentication(): bool
    {
        return $this->user->usesTwoFactorAuthentication();
    }

    /**
     * How long ago two factor authentication was turned on, in words, or null
     * when it is not in use.
     */
    public function twoFactorConfirmedAt(): ?string
    {
        return $this->user->two_factor_confirmed_at?->diffForHumans();
    }

    /**
     * The recovery codes that have not been spent yet, so somebody can copy
     * them somewhere safe and see how many they have left.
     *
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        return $this->user->two_factor_recovery_codes ?? [];
    }

    /**
     * The API keys this account has minted, newest first, each with the two
     * dates the screen shows: when it was made, and when it last let something
     * in, which is null for a key nothing has used yet.
     *
     * @return array<int, array{id: int, name: string, createdAt: string, lastUsedAt: ?string}>
     */
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
            ])
            ->all();
    }

    /**
     * The line at the head of the list, which says how many keys are in force
     * rather than making somebody count the rows.
     */
    public function apiKeysHeader(): string
    {
        $count = $this->user->tokens()->count();

        if ($count === 0) {
            return __('Active · no API keys');
        }

        return trans_choice('Active · :count API key|Active · :count API keys', $count);
    }

    /**
     * The passkeys on the account, delegated to the object that knows how the
     * list reads.
     */
    public function passkeys(): PasskeyListViewModel
    {
        return new PasskeyListViewModel(user: $this->user);
    }

    /**
     * Whether a passkey can be used at all on this instance.
     *
     * The browser has the last word, since only it knows what the device can do,
     * but the server has to answer too: the reason belongs on a screen the
     * server rendered, and refusing to remove a password cannot rest on a
     * disabled button.
     */
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    /**
     * Why passkeys cannot be used here, or null when they can.
     */
    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    /**
     * Whether the account has given its password up in favour of passkeys. The
     * password section shows a way to set one rather than a way to change one.
     */
    public function usesPasswordlessSignIn(): bool
    {
        return $this->user->usesPasswordlessSignIn();
    }

    /**
     * Whether the password may be given up: it takes a passkey to fall back on,
     * and an instance where a passkey can actually be used.
     */
    public function canRemoveThePassword(): bool
    {
        return ! $this->user->usesSingleSignOn()
            && $this->user->password !== null
            && $this->user->hasPasskeys()
            && $this->passkeysAreAvailable();
    }

    public function user(): User
    {
        return $this->user;
    }
}
