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
use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
                'client' => __((ApiKeyClientEnum::tryFrom((string) $apiKey->client) ?? ApiKeyClientEnum::Script)->label()),
                'createdAt' => $apiKey->created_at->diffForHumans(),
                'lastUsedAt' => $apiKey->last_used_at?->diffForHumans(),
                'reachesSensitiveFields' => in_array(ApiKeyScopeEnum::SensitiveFields->value, $apiKey->abilities ?? [], true),
            ])
            ->all();
    }

    /** @return list<array{clientId: string, name: string, connectedAt: string}> */
    public function connectedAssistants(): array
    {
        return $this->user->oauthAccessTokens()
            ->with('client')
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

    public function usesPasswordlessSignIn(): bool
    {
        return $this->user->usesPasswordlessSignIn();
    }

    public function canRemoveThePassword(): bool
    {
        if ($this->user->password === null) {
            return false;
        }

        if ($this->user->usesSingleSignOn()) {
            return true;
        }

        return $this->user->hasPasskeys() && $this->passkeysAreAvailable();
    }

    /**
     * Get the provider signing this user in, or null when none does.
     *
     * @return array{name: string, available: bool}|null
     */
    public function connectedProvider(): ?array
    {
        $provider = $this->user->sso_provider;

        if ($provider === null) {
            return null;
        }

        return [
            'name' => $provider->brandName(),
            'available' => SsoProviderAvailability::isAvailable($provider),
        ];
    }

    /**
     * Get the providers this user could connect, which is none once one is
     * connected, since a user signs in through at most one.
     *
     * @return list<array{name: string, url: string}>
     */
    public function providersToConnect(): array
    {
        if ($this->user->sso_provider !== null) {
            return [];
        }

        return array_map(fn (SsoProviderEnum $provider): array => [
            'name' => $provider->brandName(),
            'url' => route('settings.sso.redirect', ['provider' => $provider->value]),
        ], SsoProviderAvailability::onThisInstance());
    }

    public function canDisconnectTheProvider(): bool
    {
        return $this->user->sso_provider !== null
            && ($this->user->password !== null || $this->user->hasPasskeys());
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
