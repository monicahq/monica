<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\OAuth;

use App\Http\Controllers\Api\OAuth\AuthorizationServerMetadataController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationServerMetadataControllerTest extends TestCase
{
    #[Test]
    public function it_keeps_what_assistants_read_and_adds_what_the_mobile_apps_need(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertOk();
        $response->assertJsonPath('issuer', url('/'));
        $response->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'));
        $response->assertJsonPath('token_endpoint', route('passport.token'));
        $response->assertJsonPath('registration_endpoint', route('mcp.oauth.register'));
        $response->assertJsonPath('code_challenge_methods_supported', ['S256']);
        $response->assertJsonPath('grant_types_supported', ['authorization_code', 'refresh_token']);
        $response->assertJsonPath('revocation_endpoint', route('oauth.revocations.create'));
        $response->assertJsonPath('revocation_endpoint_auth_methods_supported', ['none']);
        $response->assertJsonPath('scopes_supported', ['mcp:use']);
        $response->assertJsonPath('prompt_values_supported', ['none', 'login', 'consent', 'create']);
    }

    #[Test]
    public function every_address_starts_with_the_path_of_an_instance_served_under_one(): void
    {
        URL::forceRootUrl('https://dundermifflin.com/monica');
        URL::forceScheme('https');

        $metadata = new AuthorizationServerMetadataController()->show()->getData(true);

        foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'revocation_endpoint'] as $member) {
            $this->assertStringStartsWith('https://dundermifflin.com/monica', $metadata[$member]);
        }
    }
}
