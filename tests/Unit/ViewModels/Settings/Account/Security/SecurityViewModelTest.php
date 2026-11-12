<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Enums\SsoProviderEnum;
use App\Models\Administrator;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_warns_that_removing_the_last_second_factor_turns_two_factor_off(): void
    {
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create();

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertEquals('Two-factor authentication turns off, and your recovery codes stop working.', $viewModel->securityKeyRemovalWarning());
    }

    #[Test]
    public function it_warns_that_two_factor_stays_on_while_another_factor_remains(): void
    {
        $user = User::factory()->twoFactor()->create();
        SecurityKey::factory()->for($user)->create();

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertEquals('Two-factor authentication stays on with your other security keys or your authenticator app.', $viewModel->securityKeyRemovalWarning());
        $this->assertEquals('Your security keys still answer the second step when you sign in.', $viewModel->authenticatorAppRemovalWarning());
    }

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
    public function it_says_when_a_key_last_answered(): void
    {
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create(['last_used_at' => now()->subDays(2)]);

        $keys = new SecurityViewModel(user: $user)->securityKeys();

        $this->assertEquals('2 days ago', $keys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_warns_that_removing_the_app_alone_turns_two_factor_off(): void
    {
        $viewModel = new SecurityViewModel(user: User::factory()->twoFactor()->create());

        $this->assertEquals('Two-factor authentication turns off, and your recovery codes stop working.', $viewModel->authenticatorAppRemovalWarning());
    }

    #[Test]
    public function it_gives_the_person_who_is_signed_in(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertTrue($user->is($viewModel->user()));
        $this->assertEquals('Michael Scott', $viewModel->user()->name);
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
        $this->assertEquals('Active · no API keys', $viewModel->apiKeysHeader());
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
    public function it_gives_how_long_ago_the_api_key_was_last_used(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;
        $apiKey->forceFill(['last_used_at' => now()->subHours(5)])->save();

        $apiKeys = new SecurityViewModel(user: $user)->apiKeys();

        $this->assertEquals('5 hours ago', $apiKeys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_counts_the_api_keys_in_the_header(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $this->assertEquals('Active · 1 API key', new SecurityViewModel(user: $user)->apiKeysHeader());

        $user->createToken('Beet farm sync');

        $this->assertEquals('Active · 2 API keys', new SecurityViewModel(user: $user)->apiKeysHeader());
    }

    #[Test]
    public function it_gives_one_line_per_connected_assistant_however_often_it_renewed_its_token(): void
    {
        $user = User::factory()->create();
        $first = $this->oauthTokenFor(user: $user);
        $this->oauthTokenFor(user: $user, client: $first->client);

        $assistants = new SecurityViewModel(user: $user)->connectedAssistants();

        $this->assertCount(1, $assistants);
        $this->assertSame((string) $first->client_id, $assistants[0]['clientId']);
        $this->assertSame('Schrute Farms Assistant', $assistants[0]['name']);
    }

    #[Test]
    public function it_leaves_out_the_assistants_that_can_no_longer_reach_the_account(): void
    {
        $user = User::factory()->create();
        $this->oauthTokenFor(user: $user, revoked: true);
        $this->oauthTokenFor(user: $user, expiresAt: now()->subMinute()->toImmutable(), refreshExpiresAt: now()->subMinute()->toImmutable());
        $this->oauthTokenFor(user: User::factory()->create());

        $this->assertSame([], new SecurityViewModel(user: $user)->connectedAssistants());
    }

    #[Test]
    public function it_keeps_an_idle_assistant_that_can_still_renew_its_access(): void
    {
        $user = User::factory()->create();
        $token = $this->oauthTokenFor(user: $user, expiresAt: now()->subDays(2)->toImmutable());

        $assistants = new SecurityViewModel(user: $user)->connectedAssistants();

        $this->assertCount(1, $assistants);
        $this->assertSame((string) $token->client_id, $assistants[0]['clientId']);
    }

    #[Test]
    public function it_tells_only_an_account_with_an_assistant_that_the_mcp_server_is_unavailable(): void
    {
        $pam = User::factory()->create();
        $dwight = User::factory()->create();
        $this->oauthTokenFor(user: $pam);
        $pam->account->features()->deactivate('mcp-server');
        $dwight->account->features()->deactivate('mcp-server');
        Feature::flushCache();

        $this->assertFalse(new SecurityViewModel(user: $pam)->mcpIsOn());
        $this->assertSame(
            'The MCP server is unavailable on this account for now. Your connected assistants are kept.',
            new SecurityViewModel(user: $pam)->assistantsNotice(),
        );
        $this->assertNull(new SecurityViewModel(user: $dwight)->assistantsNotice());
    }

    #[Test]
    public function it_says_nothing_about_the_mcp_server_while_it_is_on(): void
    {
        $pam = User::factory()->create();
        $this->oauthTokenFor(user: $pam);

        $this->assertTrue(new SecurityViewModel(user: $pam)->mcpIsOn());
        $this->assertNull(new SecurityViewModel(user: $pam)->assistantsNotice());
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
    public function it_lets_a_connected_user_remove_their_password(): void
    {
        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $this->assertTrue(new SecurityViewModel(user: $user)->canRemoveThePassword());
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
    public function it_gives_the_address_a_device_is_given(): void
    {
        $user = User::factory()->create();

        $viewModel = new SecurityViewModel(user: $user);

        $this->assertEquals(route('sabre.dav', ['path' => ''], absolute: true), $viewModel->davAddress());
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
}
