<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\InstanceSettingEnum;
use App\Enums\InstanceSettingGroupEnum;
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
    public function it_describes_every_setting(): void
    {
        foreach (InstanceSettingEnum::cases() as $setting) {
            $this->assertNotEmpty($setting->description());
            $this->assertStringEndsWith('.', $setting->description());
        }
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
                file_get_contents($file),
                "{$setting->value} names {$setting->environmentVariable()}, which ".basename($file).' never reads.'
            );
        }
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

    #[Test]
    public function it_fixes_only_what_describes_the_deployment(): void
    {
        foreach (InstanceSettingEnum::cases() as $setting) {
            $this->assertEquals(
                $setting->group() === InstanceSettingGroupEnum::Instance,
                $setting->fixedAtDeployment()
            );
        }
    }
}
