<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Models\OAuthConnection;
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

        $this->assertNotSame(['Use MCP server'], $viewModel->permissions());
    }

    #[Test]
    public function it_speaks_the_same_way_whichever_client_asks(): void
    {
        config(['monica.instance' => 'monica.dundermifflin.com']);

        $mobile = new Client(['name' => 'Monica for mobile']);
        $mobile->id = OAuthConnection::MOBILE_CLIENT_ID;

        $phone = $this->viewModel(client: $mobile, redirectUri: OAuthConnection::ANDROID_REDIRECT_URI);
        $assistant = $this->viewModel(client: new Client(['name' => 'Schrute Farms Assistant']), redirectUri: 'https://claude.ai/callback');

        $this->assertSame('Connect Monica for mobile', $phone->title());
        $this->assertSame('Connect Schrute Farms Assistant', $assistant->title());
        $this->assertStringContainsString('monica.dundermifflin.com', $assistant->description());
        $this->assertSame($phone->caution(), $assistant->caution());
        $this->assertSame($phone->notice(), $assistant->notice());
    }

    #[Test]
    public function it_names_the_return_address_by_its_host_or_else_whole(): void
    {
        $assistant = $this->viewModel(client: new Client(['name' => 'Schrute Farms Assistant']), redirectUri: 'https://claude.ai/callback');
        $phone = $this->viewModel(client: new Client(['name' => 'Monica for mobile']), redirectUri: OAuthConnection::ANDROID_REDIRECT_URI);

        $this->assertSame('claude.ai', $assistant->redirectAddress());
        $this->assertSame(OAuthConnection::ANDROID_REDIRECT_URI, $phone->redirectAddress());
    }

    private function viewModel(Client $client, string $redirectUri): OAuthAuthorizationViewModel
    {
        return new OAuthAuthorizationViewModel(
            client: $client,
            authToken: 'beet-harvest',
            user: new User(['email' => 'pam@dundermifflin.com']),
            redirectUri: $redirectUri,
            scopes: [new Scope(Registrar::OAUTH_SCOPE, 'Use MCP server')],
        );
    }
}
