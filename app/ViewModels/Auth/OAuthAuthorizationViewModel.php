<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Models\OAuthConnection;
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

    /**
     * Where the app sends the person back once they answer: the host of a web address, or the whole
     * address when it has none, as the private scheme of a phone app does.
     */
    public function redirectAddress(): string
    {
        return (string) (parse_url($this->redirectUri, PHP_URL_HOST) ?: $this->redirectUri);
    }

    public function title(): string
    {
        return __('Connect :name', ['name' => $this->clientName()]);
    }

    public function description(): string
    {
        return __(':name is asking to read and change what your account on :instance holds, on your behalf.', [
            'name' => $this->clientName(),
            'instance' => (string) config('monica.instance'),
        ]);
    }

    public function caution(): string
    {
        return __('An app names itself, so check the address it sends you back to. Only allow it if you started this connection yourself.');
    }

    public function notice(): string
    {
        return __('You can disconnect it at any time from the security settings of your account.');
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return array_map(
            fn (Scope $scope): string => match ($scope->id) {
                'mcp', Registrar::OAUTH_SCOPE => __('Use MCP server'),
                OAuthConnection::API_SCOPE => __('Use the API'),
                default => $scope->description,
            },
            array_values($this->scopes),
        );
    }
}
