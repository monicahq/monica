<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Enums\SsoProviderEnum;
use App\Models\Administrator;
use App\Models\OAuthConnection;
use App\Models\Passkey;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_security_keys_with_when_they_were_last_used(): void
    {
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create(['name' => 'Dundie key']);
        SecurityKey::factory()->create();

        $keys = new SecurityViewModel(user: $user)->securityKeys();

        $this->assertCount(1, $keys);
        $this->assertEquals('Dundie key', $keys[0]['name']);
        $this->assertNull($keys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_gives_how_long_ago_the_password_was_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subDays(2)]);

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertEquals('2 days ago', $viewModel->passwordChangedAt());
    }

    #[Test]
    public function it_gives_nothing_when_the_password_was_never_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertNull($viewModel->passwordChangedAt());
    }

    #[Test]
    public function it_says_whether_the_account_signs_in_through_a_provider(): void
    {
        $user = User::factory()->create();
        $viewModel = new SecurityViewModel(user: $user);

        $this->assertFalse($viewModel->usesSingleSignOn());

        $singleSignOnUser = User::factory()->singleSignOn()->create();
        $singleSignOnViewModel = new SecurityViewModel(user: $singleSignOnUser);

        $this->assertTrue($singleSignOnViewModel->usesSingleSignOn());
    }

    #[Test]
    public function it_gives_an_empty_list_when_no_api_key_has_been_made(): void
    {
        $user = User::factory()->create();

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertSame([], $viewModel->apiKeys());
    }

    #[Test]
    public function it_gives_the_api_keys_newest_first(): void
    {
        $user = User::factory()->create();

        $older = $user->createToken('Beet farm sync')->accessToken;
        $older->forceFill(['created_at' => now()->subDays(3)])->save();

        $user->createToken('Dundie awards bot');

        $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

        $this->assertCount(2, $apiKeys);
        $this->assertEquals('Dundie awards bot', $apiKeys[0]['name']);
        $this->assertEquals('Beet farm sync', $apiKeys[1]['name']);
        $this->assertEquals('3 days ago', $apiKeys[1]['createdAt']);
    }

    #[Test]
    public function it_gives_nothing_for_the_last_use_until_something_uses_the_key(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

        $this->assertNull($apiKeys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_lists_phones_and_assistants_together_newest_first(): void
    {
        $user = User::factory()->create();
        $assistant = Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant']);
        OAuthConnection::factory()->create(['user_id' => $user->id, 'label' => 'Pixel 9', 'created_at' => now()->subDays(2)]);
        OAuthConnection::factory()->create(['user_id' => $user->id, 'client_id' => $assistant->id, 'label' => null, 'scopes' => ['mcp:use'], 'created_at' => now()->subDay()]);
        OAuthConnection::factory()->ended()->create(['user_id' => $user->id, 'label' => 'Old phone']);
        OAuthConnection::factory()->create(['user_id' => $user->id, 'label' => 'Idle phone', 'last_used_at' => now()->subDays(31)]);
        OAuthConnection::factory()->create(['label' => 'Somebody else’s phone']);

        $connections = new SecurityViewModel(user: $user)->connections();

        $this->assertSame(['Schrute Farms Assistant', 'Pixel 9'], array_column($connections, 'name'));
        $this->assertSame(['Use MCP server'], $connections[0]['permissions']);
        $this->assertSame('Monica for mobile', $connections[1]['client']);
        $this->assertSame(['Use the API'], $connections[1]['permissions']);
        $this->assertStringStartsWith('Use MCP server · connected', $connections[0]['description']);
        $this->assertStringStartsWith('Monica for mobile · Use the API · connected', $connections[1]['description']);
    }

    #[Test]
    public function it_speaks_only_of_phones_while_the_mcp_server_is_off(): void
    {
        $pam = User::factory()->create();
        $on = new SecurityViewModel(user: $pam);
        $description = $on->connectionsDescription();
        $emptyState = $on->connectionsEmptyState();

        $pam->account->features()->deactivate('mcp-server');
        Feature::flushCache();
        $off = new SecurityViewModel(user: $pam);

        $this->assertNotSame($description, $off->connectionsDescription());
        $this->assertNotSame($emptyState, $off->connectionsEmptyState());
        $this->assertStringNotContainsString('assistant', $off->connectionsDescription().$off->connectionsHelp().$off->connectionsEmptyState());
    }

    #[Test]
    public function it_tells_only_an_account_with_a_connection_to_the_mcp_server_that_it_is_unavailable(): void
    {
        $pam = User::factory()->create();
        $dwight = User::factory()->create();
        $jim = User::factory()->create();
        OAuthConnection::factory()->create(['user_id' => $pam->id, 'scopes' => ['mcp:use']]);
        OAuthConnection::factory()->create(['user_id' => $jim->id, 'scopes' => [OAuthConnection::API_SCOPE]]);

        foreach ([$pam, $dwight, $jim] as $user) {
            $user->account->features()->deactivate('mcp-server');
        }

        Feature::flushCache();

        $this->assertFalse(new SecurityViewModel(user: $pam)->mcpIsOn());
        $this->assertNotNull(new SecurityViewModel(user: $pam)->connectionsNotice());
        $this->assertNull(new SecurityViewModel(user: $dwight)->connectionsNotice());
        $this->assertNull(new SecurityViewModel(user: $jim)->connectionsNotice());
    }

    #[Test]
    public function it_says_nothing_about_the_mcp_server_while_it_is_on(): void
    {
        $pam = User::factory()->create();
        OAuthConnection::factory()->create(['user_id' => $pam->id, 'scopes' => ['mcp:use']]);

        $this->assertTrue(new SecurityViewModel(user: $pam)->mcpIsOn());
        $this->assertNull(new SecurityViewModel(user: $pam)->connectionsNotice());
    }

    #[Test]
    public function it_lists_every_provider_the_instance_offers_whether_or_not_it_is_connected(): void
    {
        $this->offer('github');
        $this->offer('google');

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $providers = new SecurityViewModel(user: $user)->providers();

        $this->assertEquals(['GitHub', 'Google'], array_column($providers, 'name'));
        $this->assertEquals([true, false], array_column($providers, 'connected'));
        $this->assertEquals(route('settings.sso.redirect', ['provider' => 'google']), $providers[1]['connectUrl']);
        $this->assertEquals(route('settings.sso.destroy', ['provider' => 'github']), $providers[0]['disconnectUrl']);
    }

    #[Test]
    public function it_keeps_listing_a_connected_provider_the_instance_has_stopped_offering(): void
    {
        config(['monica.sso_installed' => []]);

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $providers = new SecurityViewModel(user: $user)->providers();

        $this->assertCount(1, $providers);
        $this->assertEquals('GitHub', $providers[0]['name']);
        $this->assertTrue($providers[0]['connected']);
        $this->assertFalse($providers[0]['available']);
    }

    #[Test]
    public function it_lists_no_provider_when_the_instance_offers_none_and_none_is_connected(): void
    {
        config(['monica.sso_installed' => []]);

        $this->assertEquals([], new SecurityViewModel(user: User::factory()->create())->providers());
    }

    #[Test]
    public function it_allows_disconnecting_only_while_something_else_signs_the_user_in(): void
    {
        $this->offer('github');
        $this->offer('google');

        $withPassword = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '1']))->create();
        $withNothingElse = User::factory()->singleSignOn()->create();
        $withAnotherProvider = User::factory()->singleSignOn()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '2']))->create();

        $this->assertEquals([true, false], array_column(new SecurityViewModel(user: $withPassword)->providers(), 'canDisconnect'));
        $this->assertEquals([false, false], array_column(new SecurityViewModel(user: $withNothingElse)->providers(), 'canDisconnect'));
        $this->assertEquals([true, true], array_column(new SecurityViewModel(user: $withAnotherProvider)->providers(), 'canDisconnect'));
    }

    #[Test]
    public function it_offers_to_sign_out_every_other_device_only_to_an_account_with_a_password(): void
    {
        $withPassword = User::factory()->create();
        $withoutPassword = User::factory()->create(['password' => null]);

        $this->assertTrue(new SecurityViewModel(user: $withPassword)->canSignOutOtherDevices());
        $this->assertFalse(new SecurityViewModel(user: $withoutPassword)->canSignOutOtherDevices());
    }

    #[Test]
    public function it_offers_the_address_only_while_carddav_is_on_for_the_account(): void
    {
        $jim = User::factory()->create();
        $dwight = User::factory()->create();
        $dwight->account->features()->deactivate('carddav');
        Feature::flushCache();

        $this->assertTrue(new SecurityViewModel(user: $jim)->davIsOn());
        $this->assertFalse(new SecurityViewModel(user: $dwight)->davIsOn());
    }

    #[Test]
    public function it_says_which_keys_reach_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $user->createToken('Deployment script', ['*']);
        $user->createToken('Dwight’s phone', ['*', 'sensitive-fields']);

        $keys = collect(new SecurityViewModel(user: $user)->apiKeys())->keyBy('name');

        $this->assertTrue($keys['Dwight’s phone']['reachesSensitiveFields']);
        $this->assertFalse($keys['Deployment script']['reachesSensitiveFields']);
    }

    private function offer(string $provider): void
    {
        config([
            'monica.sso_installed' => SsoProviderEnum::values(),
            "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
            "services.{$provider}.client_secret" => 'that-is-what-she-said',
            "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
        ]);
    }

    #[Test]
    public function it_offers_the_administration_scope_to_an_administrator_only_while_support_is_switched_on(): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->assertTrue(new SecurityViewModel(user: $michael)->offersAdministrationScope());
        $this->assertFalse(new SecurityViewModel(user: User::factory()->create())->offersAdministrationScope());

        config(['monica.support_enabled' => false]);

        $this->assertFalse(new SecurityViewModel(user: $michael)->offersAdministrationScope());
    }

    #[Test]
    public function it_does_not_offer_to_remove_the_password_for_a_connected_provider_without_a_passkey(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');

        $user = User::factory()->create();
        ProviderConnection::factory()->create(['user_id' => $user->id]);

        $this->assertFalse(new SecurityViewModel(user: $user)->canRemoveThePassword());
    }

    #[Test]
    public function it_offers_to_remove_the_password_when_a_passkey_can_be_used(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');

        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->assertTrue(new SecurityViewModel(user: $user)->canRemoveThePassword());
    }
}
