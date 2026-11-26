<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Models\MobileSession;
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

    public function isMobileApp(): bool
    {
        return $this->clientId() === MobileSession::CLIENT_ID;
    }

    public function title(): string
    {
        return $this->isMobileApp()
            ? __('Allow Monica for Android to reach your account?')
            : __('Connect :name', ['name' => $this->clientName()]);
    }

    public function description(): string
    {
        return $this->isMobileApp()
            ? __('The app on your phone is asking to read and change everything in your account on :instance, like the web app.', ['instance' => (string) config('monica.instance')])
            : __('This assistant is asking to read and change what your account holds, on your behalf.');
    }

    public function caution(): string
    {
        return $this->isMobileApp()
            ? __('Only allow it if you just tapped Log in or Sign up in the Monica app.')
            : __('The assistant names itself, so check the address it sends you back to. Only allow it if you started this connection yourself.');
    }

    public function notice(): string
    {
        return $this->isMobileApp()
            ? __('You can sign the phone out at any time from the security settings of your account.')
            : __('You can disconnect the assistant at any time from the security settings of your account.');
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return array_map(
            fn (Scope $scope): string => match ($scope->id) {
                'mcp', Registrar::OAUTH_SCOPE => __('Use MCP server'),
                MobileSession::SCOPE => __('Use the API'),
                default => $scope->description,
            },
            array_values($this->scopes),
        );
    }
}
