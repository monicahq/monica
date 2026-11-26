<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Enums\ApiKeyClientEnum;
use App\Enums\ApiKeyScopeEnum;
use App\Enums\SsoProviderEnum;
use App\Helpers\FlagState;
use App\Helpers\PasskeyAvailability;
use App\Helpers\PasswordRequirements;
use App\Helpers\SsoProviderAvailability;
use App\Helpers\SupportAvailability;
use App\Models\MobileSession;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly string $currentSessionId = '',
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

    public function usesAuthenticatorApp(): bool
    {
        return $this->user->usesAuthenticatorApp();
    }

    /** @return list<array{id: int, name: string, addedAt: string, lastUsedAt: string|null}> */
    public function securityKeys(): array
    {
        return $this->user->securityKeys()
            ->get()
            ->map(fn (SecurityKey $securityKey): array => [
                'id' => $securityKey->id,
                'name' => $securityKey->name,
                'addedAt' => $securityKey->created_at?->diffForHumans() ?? '',
                'lastUsedAt' => $securityKey->last_used_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    public function securityKeyRemovalWarning(): string
    {
        $factors = $this->user->securityKeys()->count();

        if ($this->usesAuthenticatorApp()) {
            $factors++;
        }

        if ($factors > 1) {
            return __('Two-factor authentication stays on with your other security keys or your authenticator app.');
        }

        return __('Two-factor authentication turns off, and your recovery codes stop working.');
    }

    public function authenticatorAppRemovalWarning(): string
    {
        if ($this->user->securityKeys()->exists()) {
            return __('Your security keys still answer the second step when you sign in.');
        }

        return __('Two-factor authentication turns off, and your recovery codes stop working.');
    }

    public function recoveryCodesRemaining(): int
    {
        return count($this->user->two_factor_recovery_codes ?? []);
    }

    public function offersAdministrationScope(): bool
    {
        if (! SupportAvailability::isEnabled()) {
            return false;
        }

        return $this->user->isAdministrator();
    }

    /** @return array<int, array{id: int, name: string, createdAt: string, lastUsedAt: ?string, reachesSensitiveFields: bool, administers: bool}> */
    public function apiKeys(): array
    {
        return $this->user->tokens()
            ->latest()
            ->get()
            ->map(fn (PersonalAccessToken $apiKey): array => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'client' => __((ApiKeyClientEnum::tryFrom((string) $apiKey->client) ?? ApiKeyClientEnum::Script)->label()),
                'createdAt' => $apiKey->created_at->diffForHumans(),
                'lastUsedAt' => $apiKey->last_used_at?->diffForHumans(),
                'reachesSensitiveFields' => in_array(ApiKeyScopeEnum::SensitiveFields->value, $apiKey->abilities ?? [], true),
                'administers' => in_array(ApiKeyScopeEnum::Administration->value, $apiKey->abilities ?? [], true),
            ])
            ->all();
    }

    /** @return list<array{clientId: string, name: string, connectedAt: string}> */
    public function connectedAssistants(): array
    {
        return $this->user->oauthAccessTokens()
            ->with('client')
            ->where('client_id', '!=', MobileSession::CLIENT_ID)
            ->where('revoked', false)
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', now())
                ->orWhereHas('refreshToken', fn (Builder $refresh) => $refresh
                    ->where('revoked', false)
                    ->where('expires_at', '>', now())))
            ->oldest()
            ->get()
            ->groupBy('client_id')
            ->map(fn (Collection $tokens): array => [
                'clientId' => (string) $tokens->first()->client_id,
                'name' => $tokens->first()->client->name,
                'connectedAt' => $tokens->first()->created_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string, signedInAt: string, lastUsedAt: string}> */
    public function mobileSessions(): array
    {
        return $this->user->mobileSessions()
            ->active()
            ->latest()
            ->get()
            ->map(fn (MobileSession $session): array => [
                'id' => $session->id,
                'name' => $session->device_name ?? __('Unnamed phone'),
                'signedInAt' => $session->created_at?->diffForHumans() ?? '',
                'lastUsedAt' => $session->last_used_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    public function mcpIsOn(): bool
    {
        return FlagState::isOn('mcp-server', $this->user->account);
    }

    public function assistantsNotice(): ?string
    {
        if ($this->mcpIsOn() || $this->connectedAssistants() === []) {
            return null;
        }

        return __('The MCP server is unavailable on this account for now. Your connected assistants are kept.');
    }

    public function davIsOn(): bool
    {
        return FlagState::isOn('carddav', $this->user->account);
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

    public function sessions(): SessionListViewModel
    {
        return new SessionListViewModel(user: $this->user, currentSessionId: $this->currentSessionId);
    }

    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    public function passwordRules(): string
    {
        return PasswordRequirements::passwordRules();
    }

    public function passwordRequirements(): string
    {
        return PasswordRequirements::description();
    }

    public function canSignOutOtherDevices(): bool
    {
        return $this->user->password !== null;
    }

    public function usesPasswordlessSignIn(): bool
    {
        return $this->user->usesPasswordlessSignIn();
    }

    public function canRemoveThePassword(): bool
    {
        if ($this->user->password === null) {
            return false;
        }

        return $this->user->hasPasskeys() && $this->passkeysAreAvailable();
    }

    /**
     * Get every provider this instance offers, with the ones the user connected,
     * and a connected one the instance no longer offers so it can be said so.
     *
     * @return list<array{name: string, connected: bool, available: bool, canDisconnect: bool, connectUrl: string, disconnectUrl: string}>
     */
    public function providers(): array
    {
        $connected = $this->user->providerConnections
            ->map(fn (ProviderConnection $connection): SsoProviderEnum => $connection->provider)
            ->all();

        $providers = array_filter(
            SsoProviderEnum::cases(),
            fn (SsoProviderEnum $provider): bool => in_array($provider, $connected, true) || SsoProviderAvailability::isAvailable($provider),
        );

        return array_values(array_map(fn (SsoProviderEnum $provider): array => [
            'name' => $provider->brandName(),
            'connected' => in_array($provider, $connected, true),
            'available' => SsoProviderAvailability::isAvailable($provider),
            'canDisconnect' => $this->canDisconnect($provider, $connected),
            'connectUrl' => route('settings.sso.redirect', ['provider' => $provider->value]),
            'disconnectUrl' => route('settings.sso.destroy', ['provider' => $provider->value]),
        ], $providers));
    }

    /**
     * Whether the provider is connected and something else still signs the user in once it is gone.
     *
     * @param  list<SsoProviderEnum>  $connected
     */
    private function canDisconnect(SsoProviderEnum $provider, array $connected): bool
    {
        if (! in_array($provider, $connected, true)) {
            return false;
        }

        if ($this->user->password !== null || $this->user->hasPasskeys()) {
            return true;
        }

        foreach ($connected as $other) {
            if ($other === $provider) {
                continue;
            }

            if (SsoProviderAvailability::isAvailable($other)) {
                return true;
            }
        }

        return false;
    }

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    public function user(): User
    {
        return $this->user;
    }
}
