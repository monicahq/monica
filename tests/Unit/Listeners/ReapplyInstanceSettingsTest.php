<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Listeners\ReapplyInstanceSettings;
use App\Models\InstanceSetting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReapplyInstanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_long_lived_process_up_to_date_before_the_next_job(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();
        InstanceSettings::apply();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        $this->assertSame(30, config('monica.free_contact_limit'));

        new ReapplyInstanceSettings()->handle($this->job());

        $this->assertSame(5, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_reads_the_cache_rather_than_the_table_on_every_job(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);
        InstanceSettings::stored();

        InstanceSetting::query()->getConnection()->table('instance_settings')->delete();

        new ReapplyInstanceSettings()->handle($this->job());

        $this->assertSame(5, config('monica.free_contact_limit'));
        $this->assertTrue(Cache::has(InstanceSetting::CACHE_KEY));
    }

    #[Test]
    public function it_sees_a_change_at_once_because_a_write_forgets_the_entry(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();
        InstanceSettings::stored();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        new ReapplyInstanceSettings()->handle($this->job());

        $this->assertSame(5, config('monica.free_contact_limit'));
    }

    private function job(): JobProcessing
    {
        return new JobProcessing('database', $this->createMock(Job::class));
    }
}
