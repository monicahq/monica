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
use App\Models\OAuthConnection;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Laravel\Mcp\Server\Registrar;
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

    /** @return list<array{id: int, name: string, client: string, permissions: list<string>, description: string}> */
    public function connections(): array
    {
        return $this->user->oauthConnections()
            ->with('client')
            ->active()
            ->latest()
            ->get()
            ->map(function (OAuthConnection $connection): array {
                $permissions = array_map(fn (string $scope): string => match ($scope) {
                    Registrar::OAUTH_SCOPE => __('Use MCP server'),
                    OAuthConnection::API_SCOPE => __('Use the API'),
                    default => $scope,
                }, $connection->scopes ?? []);

                $description = collect([
                    $connection->client->name === $connection->name ? null : $connection->client->name,
                    implode(', ', $permissions) ?: null,
                    __('connected :connected, last used :lastUsed', [
                        'connected' => $connection->created_at?->diffForHumans() ?? '',
                        'lastUsed' => $connection->last_used_at->diffForHumans(),
                    ]),
                ])->filter()->implode(' · ');

                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'client' => $connection->client->name,
                    'permissions' => $permissions,
                    'description' => $description,
                ];
            })
            ->values()
            ->all();
    }

    public function mcpIsOn(): bool
    {
        return FlagState::isOn('mcp-server', $this->user->account);
    }

    public function connectionsDescription(): string
    {
        return $this->mcpIsOn()
            ? __('Every app you allowed through OAuth, the Monica app on a phone or an assistant, acts as you with the permissions it asked for.')
            : __('Every phone you signed in through the Monica app acts as you until it signs out or you disconnect it.');
    }

    public function connectionsHelp(): string
    {
        return $this->mcpIsOn()
            ? __('An app you allowed to reach your account, such as the Monica app on your phone or an AI assistant like Claude. It does so in your name, until it signs out or you disconnect it here.')
            : __('The Monica app on your phone, once you allowed it to reach your account. It does so in your name, until it signs out or you disconnect it here.');
    }

    public function connectionsEmptyState(): string
    {
        return $this->mcpIsOn()
            ? __('Log in from the Monica app on your phone, or point an assistant at the MCP server of this instance, and it appears here so you can disconnect it.')
            : __('Log in from the Monica app on your phone and it appears here, so you can disconnect it if you lose it.');
    }

    public function connectionsNotice(): ?string
    {
        if ($this->mcpIsOn()) {
            return null;
        }

        $reachesTheMcpServer = $this->user->oauthConnections()
            ->active()
            ->get()
            ->contains(fn (OAuthConnection $connection): bool => in_array(Registrar::OAUTH_SCOPE, $connection->scopes ?? [], true));

        if (! $reachesTheMcpServer) {
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
