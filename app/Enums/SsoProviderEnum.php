<?php

declare(strict_types=1);

namespace App\Enums;

enum SsoProviderEnum: string
{
    case GitHub = 'github';
    case Google = 'google';
    case Apple = 'apple';
    case Entra = 'entra';
    case Facebook = 'facebook';
    case Keycloak = 'keycloak';
    case Auth0 = 'auth0';
    case Okta = 'okta';
    case OpenIdConnect = 'openidconnect';

    /**
     * Get the provider's name as its own company writes it. It is a proper noun,
     * identical in every language, so it is not translated and this enum carries
     * no `label()`. The copy around it, `Continue with :provider`, is.
     */
    public function brandName(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Google => 'Google',
            self::Apple => 'Apple',
            self::Entra => 'Microsoft',
            self::Facebook => 'Facebook',
            self::Keycloak => 'Keycloak',
            self::Auth0 => 'Auth0',
            self::Okta => 'Okta',
            self::OpenIdConnect => (string) (config('services.openidconnect.name') ?: 'OpenID Connect'),
        };
    }

    /**
     * Get the keys of `services.{value}` that must be filled before the provider is offered.
     *
     * @return list<string>
     */
    public function requiredSettings(): array
    {
        return match ($this) {
            self::Apple => filled(config('services.apple.private_key'))
                ? ['client_id', 'team_id', 'key_id', 'private_key', 'redirect']
                : ['client_id', 'client_secret', 'redirect'],
            self::Keycloak => ['client_id', 'client_secret', 'redirect', 'server_url', 'realm'],
            self::Auth0, self::Okta => ['client_id', 'client_secret', 'redirect', 'domain'],
            self::OpenIdConnect => ['client_id', 'client_secret', 'redirect', 'base_url'],
            default => ['client_id', 'client_secret', 'redirect'],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
