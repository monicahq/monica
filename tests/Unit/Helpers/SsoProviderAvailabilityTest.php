<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\SsoProviderEnum;
use App\Helpers\SsoProviderAvailability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoProviderAvailabilityTest extends TestCase
{
    #[Test]
    public function it_offers_a_provider_that_is_installed_and_configured(): void
    {
        $this->install(['github']);
        $this->configure('github');

        $this->assertTrue(SsoProviderAvailability::isAvailable(SsoProviderEnum::GitHub));
        $this->assertEquals([SsoProviderEnum::GitHub], SsoProviderAvailability::onThisInstance());
        $this->assertTrue(SsoProviderAvailability::anyOnThisInstance());
    }

    #[Test]
    public function it_refuses_a_provider_whose_package_is_absent(): void
    {
        $this->install(['github', 'google', 'facebook']);
        $this->configure('entra');

        $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::Entra));
        $this->assertEquals([], SsoProviderAvailability::onThisInstance());
        $this->assertFalse(SsoProviderAvailability::anyOnThisInstance());
    }

    #[Test]
    public function it_refuses_a_provider_nobody_gave_credentials_for(): void
    {
        $this->install(['github']);

        $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::GitHub));
        $this->assertEquals([], SsoProviderAvailability::onThisInstance());
    }

    #[Test]
    public function it_refuses_a_provider_missing_any_one_of_its_three_credentials(): void
    {
        $this->install(['github']);

        foreach (['client_id', 'client_secret', 'redirect'] as $missing) {
            $this->configure('github');
            config(["services.github.{$missing}" => null]);

            $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::GitHub));
        }
    }

    #[Test]
    public function it_offers_nothing_when_no_driver_is_installed_at_all(): void
    {
        $this->install([]);
        $this->configure('github');
        $this->configure('google');

        $this->assertEquals([], SsoProviderAvailability::onThisInstance());
        $this->assertFalse(SsoProviderAvailability::anyOnThisInstance());
    }

    #[Test]
    public function it_keeps_the_order_the_enum_declares(): void
    {
        $this->install(['github', 'google', 'facebook']);
        $this->configure('facebook');
        $this->configure('github');
        $this->configure('google');

        $this->assertEquals(
            [SsoProviderEnum::GitHub, SsoProviderEnum::Google, SsoProviderEnum::Facebook],
            SsoProviderAvailability::onThisInstance(),
        );
    }

    #[Test]
    public function it_offers_the_generic_provider_only_once_its_address_is_supplied(): void
    {
        $this->install(['openidconnect']);
        $this->configure('openidconnect');
        config(['services.openidconnect.base_url' => null]);

        $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::OpenIdConnect));

        config(['services.openidconnect.base_url' => 'https://id.dundermifflin.com']);

        $this->assertTrue(SsoProviderAvailability::isAvailable(SsoProviderEnum::OpenIdConnect));
    }

    #[Test]
    #[DataProvider('settingsThatLocateAProvider')]
    public function it_offers_a_named_provider_only_once_everything_locating_it_is_supplied(SsoProviderEnum $provider, string $setting): void
    {
        $this->install([$provider->value]);
        $this->configure($provider->value);

        foreach ($provider->requiredSettings() as $required) {
            config(["services.{$provider->value}.{$required}" => 'dundermifflin']);
        }

        $this->assertTrue(SsoProviderAvailability::isAvailable($provider));

        config(["services.{$provider->value}.{$setting}" => null]);

        $this->assertFalse(SsoProviderAvailability::isAvailable($provider));
    }

    #[Test]
    public function it_offers_apple_with_no_secret_since_its_key_signs_one(): void
    {
        $this->install(['apple']);
        config([
            'services.apple.client_id' => 'com.dundermifflin.monica',
            'services.apple.client_secret' => null,
            'services.apple.team_id' => 'DUNDER1234',
            'services.apple.key_id' => 'SCRANTON12',
            'services.apple.private_key' => '/etc/monica/AuthKey_SCRANTON12.p8',
            'services.apple.redirect' => 'https://monica.test/sso/apple/callback',
        ]);

        $this->assertTrue(SsoProviderAvailability::isAvailable(SsoProviderEnum::Apple));

        config(['services.apple.team_id' => null]);

        $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::Apple));
    }

    #[Test]
    public function it_offers_apple_with_a_secret_generated_in_advance_and_no_key(): void
    {
        $this->install(['apple']);
        config([
            'services.apple.client_id' => 'com.dundermifflin.monica',
            'services.apple.client_secret' => 'eyJhbGciOiJFUzI1NiJ9.scranton.branch',
            'services.apple.private_key' => null,
            'services.apple.redirect' => 'https://monica.test/sso/apple/callback',
        ]);

        $this->assertTrue(SsoProviderAvailability::isAvailable(SsoProviderEnum::Apple));

        config(['services.apple.client_secret' => null]);

        $this->assertFalse(SsoProviderAvailability::isAvailable(SsoProviderEnum::Apple));
    }

    /** @return array<string, array{SsoProviderEnum, string}> */
    public static function settingsThatLocateAProvider(): array
    {
        return [
            'Keycloak without its server' => [SsoProviderEnum::Keycloak, 'server_url'],
            'Keycloak without its realm' => [SsoProviderEnum::Keycloak, 'realm'],
            'Auth0 without its domain' => [SsoProviderEnum::Auth0, 'domain'],
            'Okta without its domain' => [SsoProviderEnum::Okta, 'domain'],
        ];
    }

    /** @param  list<string>  $providers */
    private function install(array $providers): void
    {
        config(['monica.sso_installed' => $providers]);
    }

    private function configure(string $provider): void
    {
        config([
            "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
            "services.{$provider}.client_secret" => 'that-is-what-she-said',
            "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
        ]);
    }
}
