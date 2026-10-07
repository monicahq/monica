<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Models\User;
use App\ViewModels\Auth\OAuthAuthorizationViewModel;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthAuthorizationViewModelTest extends TestCase
{
    #[Test]
    public function it_names_the_permission_in_the_language_of_the_reader(): void
    {
        app()->setLocale('fr_FR');

        $viewModel = new OAuthAuthorizationViewModel(
            client: new Client(['name' => 'Schrute Farms Assistant']),
            authToken: 'beet-harvest',
            user: new User(['email' => 'dwight@dundermifflin.com']),
            redirectUri: 'https://claude.ai/callback',
            scopes: [new Scope(Registrar::OAUTH_SCOPE, 'Use MCP server')],
        );

        $this->assertSame(['Utiliser le serveur MCP'], $viewModel->permissions());
    }
}
