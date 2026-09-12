<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SsoProviderEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoProviderEnumTest extends TestCase
{
    #[Test]
    public function it_is_backed_by_the_driver_name_socialite_knows(): void
    {
        $this->assertEquals('github', SsoProviderEnum::GitHub->value);
        $this->assertEquals('google', SsoProviderEnum::Google->value);
        $this->assertEquals('microsoft', SsoProviderEnum::Microsoft->value);
        $this->assertEquals('facebook', SsoProviderEnum::Facebook->value);
    }

    #[Test]
    public function it_names_every_provider_the_way_its_own_company_writes_it(): void
    {
        $this->assertEquals('GitHub', SsoProviderEnum::GitHub->brandName());
        $this->assertEquals('Google', SsoProviderEnum::Google->brandName());
        $this->assertEquals('Microsoft', SsoProviderEnum::Microsoft->brandName());
        $this->assertEquals('Facebook', SsoProviderEnum::Facebook->brandName());
    }

    #[Test]
    public function it_names_a_driver_class_only_for_the_provider_that_needs_a_package(): void
    {
        $this->assertNull(SsoProviderEnum::GitHub->driverClass());
        $this->assertNull(SsoProviderEnum::Google->driverClass());
        $this->assertNull(SsoProviderEnum::Facebook->driverClass());
        $this->assertEquals('SocialiteProviders\Microsoft\Provider', SsoProviderEnum::Microsoft->driverClass());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['github', 'google', 'microsoft', 'facebook'], SsoProviderEnum::values());
    }
}
