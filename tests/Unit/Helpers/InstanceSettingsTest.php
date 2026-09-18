<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\InstanceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class InstanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_stored_value_in_front_of_the_deployment_one(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        InstanceSettings::apply();

        $this->assertSame(5, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_keeps_the_deployment_value_readable_after_the_override(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        InstanceSettings::apply();

        $this->assertSame(30, config('instance_deployment.free_contact_limit'));
    }

    #[Test]
    public function it_leaves_configuration_alone_when_nothing_is_stored(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        InstanceSettings::apply();

        $this->assertSame(30, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_returns_authority_to_the_deployment_when_the_row_goes(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        $setting = InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);
        InstanceSettings::apply();

        $setting->delete();
        InstanceSettings::apply();

        $this->assertSame(30, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_never_overrides_a_setting_the_deployment_fixes(): void
    {
        config()->set('monica.self_hosted', false);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::SelfHosted->value,
            'value' => '1',
        ]);

        InstanceSettings::apply();

        $this->assertFalse(config('monica.self_hosted'));
    }

    #[Test]
    public function it_reads_a_stored_value_back_as_the_type_the_key_expects(): void
    {
        config()->set('monica.magic_link_enabled', true);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MagicLinkEnabled->value,
            'value' => '0',
        ]);

        InstanceSettings::apply();

        $this->assertFalse(config('monica.magic_link_enabled'));
    }

    #[Test]
    public function it_answers_as_though_nothing_were_stored_when_the_table_cannot_be_read(): void
    {
        Cache::shouldReceive('remember')->once()->andThrow(new RuntimeException('no database yet'));

        $this->assertSame([], InstanceSettings::stored());
    }
}
