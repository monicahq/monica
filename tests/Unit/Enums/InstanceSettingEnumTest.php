<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\InstanceSettingEnum;
use App\Enums\InstanceSettingGroupEnum;
use App\Enums\InstanceSettingKindEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstanceSettingEnumTest extends TestCase
{
    #[Test]
    public function it_names_a_configuration_key_that_resolves_for_every_setting(): void
    {
        foreach (InstanceSettingEnum::cases() as $setting) {
            $this->assertTrue(
                config()->has($setting->configKey()),
                "{$setting->value} names {$setting->configKey()}, which no configuration file carries."
            );
        }
    }

    #[Test]
    public function it_never_points_two_settings_at_one_configuration_key(): void
    {
        $keys = array_map(fn (InstanceSettingEnum $s): string => $s->configKey(), InstanceSettingEnum::cases());

        $this->assertEquals($keys, array_unique($keys));
    }

    #[Test]
    public function it_names_an_environment_variable_the_matching_configuration_file_actually_reads(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        foreach (InstanceSettingEnum::cases() as $setting) {
            $file = config_path(explode('.', $setting->configKey())[0].'.php');

            if (! file_exists($file)) {
                $this->assertStringContainsString(
                    $setting->environmentVariable().'=',
                    $example,
                    "{$setting->value} reads a key a package publishes, so .env.example has to document {$setting->environmentVariable()}."
                );

                continue;
            }

            $this->assertStringContainsString(
                "'{$setting->environmentVariable()}'",
                file_get_contents($file).file_get_contents(config_path('oidc.php')),
                "{$setting->value} names {$setting->environmentVariable()}, which ".basename($file).' never reads.'
            );
        }
    }

    #[Test]
    public function it_lets_an_administrator_supply_every_provider_carried_by_openid_connect(): void
    {
        $settings = [
            [InstanceSettingEnum::EntraTenant, 'services.entra.tenant', 'ENTRA_TENANT', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::KeycloakServerUrl, 'services.keycloak.server_url', 'KEYCLOAK_SERVER_URL', InstanceSettingKindEnum::Url],
            [InstanceSettingEnum::KeycloakRealm, 'services.keycloak.realm', 'KEYCLOAK_REALM', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::KeycloakClientId, 'services.keycloak.client_id', 'KEYCLOAK_CLIENT_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::KeycloakClientSecret, 'services.keycloak.client_secret', 'KEYCLOAK_CLIENT_SECRET', InstanceSettingKindEnum::Secret],
            [InstanceSettingEnum::Auth0Domain, 'services.auth0.domain', 'AUTH0_DOMAIN', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::Auth0ClientId, 'services.auth0.client_id', 'AUTH0_CLIENT_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::Auth0ClientSecret, 'services.auth0.client_secret', 'AUTH0_CLIENT_SECRET', InstanceSettingKindEnum::Secret],
            [InstanceSettingEnum::OktaDomain, 'services.okta.domain', 'OKTA_DOMAIN', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::OktaAuthServer, 'services.okta.auth_server', 'OKTA_AUTH_SERVER', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::OktaClientId, 'services.okta.client_id', 'OKTA_CLIENT_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::OktaClientSecret, 'services.okta.client_secret', 'OKTA_CLIENT_SECRET', InstanceSettingKindEnum::Secret],
            [InstanceSettingEnum::OpenIdConnectBaseUrl, 'services.openidconnect.base_url', 'OIDC_BASE_URL', InstanceSettingKindEnum::Url],
            [InstanceSettingEnum::OpenIdConnectName, 'services.openidconnect.name', 'OIDC_NAME', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::OpenIdConnectClientId, 'services.openidconnect.client_id', 'OIDC_CLIENT_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::OpenIdConnectClientSecret, 'services.openidconnect.client_secret', 'OIDC_CLIENT_SECRET', InstanceSettingKindEnum::Secret],
        ];

        foreach ($settings as [$setting, $configKey, $environmentVariable, $kind]) {
            $this->assertEquals($configKey, $setting->configKey());
            $this->assertEquals($environmentVariable, $setting->environmentVariable());
            $this->assertEquals($kind, $setting->kind());
            $this->assertEquals(InstanceSettingGroupEnum::SingleSignOn, $setting->group());
        }
    }

    #[Test]
    public function it_lets_an_administrator_supply_apple(): void
    {
        $settings = [
            [InstanceSettingEnum::AppleClientId, 'services.apple.client_id', 'APPLE_CLIENT_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::AppleTeamId, 'services.apple.team_id', 'APPLE_TEAM_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::AppleKeyId, 'services.apple.key_id', 'APPLE_KEY_ID', InstanceSettingKindEnum::Text],
            [InstanceSettingEnum::ApplePrivateKey, 'services.apple.private_key', 'APPLE_PRIVATE_KEY', InstanceSettingKindEnum::Secret],
            [InstanceSettingEnum::AppleClientSecret, 'services.apple.client_secret', 'APPLE_CLIENT_SECRET', InstanceSettingKindEnum::Secret],
        ];

        foreach ($settings as [$setting, $configKey, $environmentVariable, $kind]) {
            $this->assertEquals($configKey, $setting->configKey());
            $this->assertEquals($environmentVariable, $setting->environmentVariable());
            $this->assertEquals($kind, $setting->kind());
            $this->assertEquals(InstanceSettingGroupEnum::SingleSignOn, $setting->group());
        }
    }

    #[Test]
    public function it_lets_an_administrator_switch_whats_new(): void
    {
        $setting = InstanceSettingEnum::ChangelogEnabled;

        $this->assertEquals('monica.changelog_enabled', $setting->configKey());
        $this->assertEquals('MONICA_CHANGELOG_ENABLED', $setting->environmentVariable());
        $this->assertEquals(InstanceSettingKindEnum::Boolean, $setting->kind());
        $this->assertFalse($setting->fixedAtDeployment());
    }

    #[Test]
    public function it_says_the_apple_settings_have_no_provider_when_its_package_is_absent(): void
    {
        config(['monica.sso_installed' => ['github', 'google']]);

        $this->assertFalse(InstanceSettingEnum::ApplePrivateKey->providerIsInstalled());
        $this->assertTrue(InstanceSettingEnum::GoogleClientId->providerIsInstalled());

        config(['monica.sso_installed' => ['github', 'google', 'apple']]);

        $this->assertTrue(InstanceSettingEnum::ApplePrivateKey->providerIsInstalled());
    }

    #[Test]
    public function it_never_points_two_settings_at_one_environment_variable(): void
    {
        $variables = array_map(
            fn (InstanceSettingEnum $s): string => $s->environmentVariable(),
            InstanceSettingEnum::cases()
        );

        $this->assertEquals($variables, array_unique($variables));
    }

    #[Test]
    public function it_keeps_infrastructure_out_of_the_catalogue(): void
    {
        foreach (InstanceSettingEnum::cases() as $setting) {
            $this->assertStringStartsNotWith('database.', $setting->configKey());
            $this->assertStringStartsNotWith('queue.', $setting->configKey());
            $this->assertStringStartsNotWith('cache.', $setting->configKey());
            $this->assertStringStartsNotWith('session.', $setting->configKey());
            $this->assertStringStartsNotWith('filesystems.', $setting->configKey());
            $this->assertNotEquals('app.key', $setting->configKey());
        }
    }
}
