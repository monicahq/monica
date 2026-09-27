<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Models\User;
use Laravel\Passport\Client;

class OAuthAuthorizationViewModel extends GuestViewModel
{
    public function __construct(
        private readonly Client $client,
        private readonly string $authToken,
        private readonly User $user,
        private readonly string $redirectUri,
        public readonly array $scopes,
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
}
