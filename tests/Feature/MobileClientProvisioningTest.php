<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileClientProvisioningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_instance_holds_the_public_client_of_the_mobile_apps(): void
    {
        $client = Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID);

        $this->assertFalse($client->confidential());
        $this->assertSame(OAuthConnection::MOBILE_REDIRECT_URIS, $client->redirect_uris);
        $this->assertSame(['authorization_code', 'refresh_token'], $client->grant_types);
        $this->assertFalse($client->revoked);
    }

    #[Test]
    public function running_the_provisioning_again_leaves_one_client(): void
    {
        $migration = require database_path('migrations/2026_11_26_000001_provision_the_mobile_oauth_client.php');

        $migration->up();
        $migration->up();

        $this->assertSame(1, Client::query()->whereKey(OAuthConnection::MOBILE_CLIENT_ID)->count());
    }

    #[Test]
    public function letting_the_ios_app_sign_in_again_changes_nothing(): void
    {
        $migration = require database_path('migrations/2026_11_27_000000_let_the_ios_app_sign_in.php');

        $clients = Client::query()->count();

        $migration->up();
        $migration->up();

        $this->assertSame($clients, Client::query()->count());
        $client = Client::query()->findOrFail(OAuthConnection::MOBILE_CLIENT_ID);
        $this->assertSame(['com.monicahq.android:/oauth/callback', 'com.monicahq.ios:/oauth/callback'], $client->redirect_uris);
    }

    #[Test]
    public function letting_the_ios_app_sign_in_keeps_the_android_phones_signed_in(): void
    {
        $tokens = $this->signInThroughOAuth(User::factory()->create());

        $migration = require database_path('migrations/2026_11_27_000000_let_the_ios_app_sign_in.php');
        $migration->up();

        $this->withHeader('Authorization', 'Basic '.base64_encode(OAuthConnection::MOBILE_CLIENT_ID.':'))
            ->postJson('/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens['refresh_token'],
                'scope' => OAuthConnection::API_SCOPE,
            ])->assertOk();

        $this->assertNull(OAuthConnection::query()->sole()->ended_at);
    }
}
