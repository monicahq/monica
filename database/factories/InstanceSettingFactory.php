<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InstanceSettingEnum;
use App\Models\InstanceSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<InstanceSetting> */
class InstanceSettingFactory extends Factory
{
    protected $model = InstanceSetting::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => (string) fake()->numberBetween(1, 500),
        ];
    }
}
