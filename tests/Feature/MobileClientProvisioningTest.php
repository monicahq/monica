<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileSession;
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
        $client = Client::query()->findOrFail(MobileSession::CLIENT_ID);

        $this->assertFalse($client->confidential());
        $this->assertSame([MobileSession::REDIRECT_URI], $client->redirect_uris);
        $this->assertSame(['authorization_code', 'refresh_token'], $client->grant_types);
        $this->assertFalse($client->revoked);
    }

    #[Test]
    public function running_the_provisioning_again_leaves_one_client(): void
    {
        $migration = require database_path('migrations/2026_11_26_000001_provision_the_mobile_oauth_client.php');

        $migration->up();
        $migration->up();

        $this->assertSame(1, Client::query()->whereKey(MobileSession::CLIENT_ID)->count());
    }
}
