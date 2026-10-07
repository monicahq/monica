<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Models\User;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;

class OAuthAuthorizationViewModel extends GuestViewModel
{
    public function __construct(
        private readonly Client $client,
        private readonly string $authToken,
        private readonly User $user,
        private readonly string $redirectUri,
        private readonly array $scopes,
    ) {}

    public function clientId(): string
    {
        return (string) $this->client->getKey();
    }

    public function clientName(): string
    {
        return $this->client->name;
    }

    public function authToken(): string
    {
        return $this->authToken;
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function redirectHost(): string
    {
        return (string) parse_url($this->redirectUri, PHP_URL_HOST);
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return array_map(
            fn (Scope $scope): string => match ($scope->id) {
                'mcp', Registrar::OAUTH_SCOPE => __('Use MCP server'),
                default => $scope->description,
            },
            array_values($this->scopes),
        );
    }
}
