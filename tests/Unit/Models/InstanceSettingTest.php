<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\InstanceSettingEnum;
use App\Models\InstanceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstanceSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_encrypts_the_value_and_reads_it_back(): void
    {
        $setting = InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'that-is-what-she-said',
        ]);

        $this->assertEquals('that-is-what-she-said', $setting->fresh()->value);

        $stored = DB::table('instance_settings')->where('id', $setting->id)->value('value');

        $this->assertNotEquals('that-is-what-she-said', $stored);
    }

    #[Test]
    public function it_forgets_the_cache_entry_when_a_row_is_saved(): void
    {
        Cache::forever(InstanceSetting::CACHE_KEY, ['stale' => 'reading']);

        InstanceSetting::factory()->create();

        $this->assertFalse(Cache::has(InstanceSetting::CACHE_KEY));
    }

    #[Test]
    public function it_forgets_the_cache_entry_when_a_row_is_deleted(): void
    {
        $setting = InstanceSetting::factory()->create();

        Cache::forever(InstanceSetting::CACHE_KEY, ['stale' => 'reading']);

        $setting->delete();

        $this->assertFalse(Cache::has(InstanceSetting::CACHE_KEY));
    }
}
