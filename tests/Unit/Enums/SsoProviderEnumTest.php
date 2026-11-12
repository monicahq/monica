<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SsoProviderEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoProviderEnumTest extends TestCase
{
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
