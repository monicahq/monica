<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Models\MobileSession;
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
    public function it_speaks_about_the_app_when_the_mobile_app_asks(): void
    {
        config(['monica.instance' => 'monica.dundermifflin.com']);

        $client = new Client(['name' => 'Monica for mobile']);
        $client->id = MobileSession::CLIENT_ID;

        $viewModel = new OAuthAuthorizationViewModel(
            client: $client,
            authToken: 'beet-harvest',
            user: new User(['email' => 'pam@dundermifflin.com']),
            redirectUri: MobileSession::ANDROID_REDIRECT_URI,
            scopes: [new Scope(MobileSession::SCOPE, 'Use the API')],
        );

        $this->assertTrue($viewModel->isMobileApp());
        $this->assertSame('Allow Monica for Android to reach your account?', $viewModel->title());
        $this->assertStringContainsString('monica.dundermifflin.com', $viewModel->description());
        $this->assertSame(['Use the API'], $viewModel->permissions());
    }

    #[Test]
    public function it_names_the_ios_app_when_the_iphone_asks(): void
    {
        $client = new Client(['name' => 'Monica for mobile']);
        $client->id = MobileSession::CLIENT_ID;

        $viewModel = new OAuthAuthorizationViewModel(
            client: $client,
            authToken: 'beet-harvest',
            user: new User(['email' => 'pam@dundermifflin.com']),
            redirectUri: MobileSession::IOS_REDIRECT_URI,
            scopes: [new Scope(MobileSession::SCOPE, 'Use the API')],
        );

        $this->assertTrue($viewModel->isMobileApp());
        $this->assertSame('Allow Monica for iOS to reach your account?', $viewModel->title());
    }

    #[Test]
    public function it_speaks_about_the_assistant_when_an_assistant_asks(): void
    {
        $viewModel = new OAuthAuthorizationViewModel(
            client: new Client(['name' => 'Schrute Farms Assistant']),
            authToken: 'beet-harvest',
            user: new User(['email' => 'dwight@dundermifflin.com']),
            redirectUri: 'https://claude.ai/callback',
            scopes: [new Scope(Registrar::OAUTH_SCOPE, 'Use MCP server')],
        );

        $this->assertFalse($viewModel->isMobileApp());
        $this->assertSame('Connect Schrute Farms Assistant', $viewModel->title());
    }
}
