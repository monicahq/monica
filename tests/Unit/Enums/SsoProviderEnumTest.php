<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SsoProviderEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoProviderEnumTest extends TestCase
{
    #[Test]
    public function it_lists_its_values_in_the_order_the_screens_offer_them(): void
    {
        $this->assertEquals(
            ['github', 'google', 'apple', 'entra', 'facebook', 'keycloak', 'auth0', 'okta', 'openidconnect'],
            SsoProviderEnum::values(),
        );
    }

    #[Test]
    public function it_names_every_provider_the_way_its_own_company_writes_it(): void
    {
        $this->assertEquals('GitHub', SsoProviderEnum::GitHub->brandName());
        $this->assertEquals('Google', SsoProviderEnum::Google->brandName());
        $this->assertEquals('Apple', SsoProviderEnum::Apple->brandName());
        $this->assertEquals('Microsoft', SsoProviderEnum::Entra->brandName());
        $this->assertEquals('Facebook', SsoProviderEnum::Facebook->brandName());
        $this->assertEquals('Keycloak', SsoProviderEnum::Keycloak->brandName());
        $this->assertEquals('Auth0', SsoProviderEnum::Auth0->brandName());
        $this->assertEquals('Okta', SsoProviderEnum::Okta->brandName());
    }

    #[Test]
    public function it_names_the_generic_provider_the_way_the_operator_did(): void
    {
        config(['services.openidconnect.name' => 'Dunder Mifflin Identity']);

        $this->assertEquals('Dunder Mifflin Identity', SsoProviderEnum::OpenIdConnect->brandName());
    }

    #[Test]
    public function it_names_the_generic_provider_after_its_protocol_when_the_operator_gave_no_name(): void
    {
        config(['services.openidconnect.name' => null]);

        $this->assertEquals('OpenID Connect', SsoProviderEnum::OpenIdConnect->brandName());
    }

    #[Test]
    public function it_requires_three_credentials_of_every_provider_and_whatever_locates_its_own(): void
    {
        $credentials = ['client_id', 'client_secret', 'redirect'];

        $this->assertEquals($credentials, SsoProviderEnum::GitHub->requiredSettings());
        $this->assertEquals($credentials, SsoProviderEnum::Google->requiredSettings());
        $this->assertEquals($credentials, SsoProviderEnum::Entra->requiredSettings());
        $this->assertEquals($credentials, SsoProviderEnum::Facebook->requiredSettings());
        $this->assertEquals([...$credentials, 'server_url', 'realm'], SsoProviderEnum::Keycloak->requiredSettings());
        $this->assertEquals([...$credentials, 'domain'], SsoProviderEnum::Auth0->requiredSettings());
        $this->assertEquals([...$credentials, 'domain'], SsoProviderEnum::Okta->requiredSettings());
        $this->assertEquals([...$credentials, 'base_url'], SsoProviderEnum::OpenIdConnect->requiredSettings());
    }

    #[Test]
    public function it_requires_what_signs_with_the_apple_key_once_a_key_is_supplied(): void
    {
        config(['services.apple.private_key' => '/etc/monica/AuthKey_SCRANTON12.p8']);

        $this->assertEquals(
            ['client_id', 'team_id', 'key_id', 'private_key', 'redirect'],
            SsoProviderEnum::Apple->requiredSettings(),
        );
    }

    #[Test]
    public function it_requires_a_secret_generated_in_advance_of_apple_when_no_key_is_supplied(): void
    {
        config(['services.apple.private_key' => null]);

        $this->assertEquals(['client_id', 'client_secret', 'redirect'], SsoProviderEnum::Apple->requiredSettings());
    }
}
