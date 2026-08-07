<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanEnum;
use App\Enums\SizeRangeEnum;
use App\Enums\WorkModeEnum;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'legal_name' => $name.' Inc.',
            'website_url' => fake()->url(),
            'industry' => 'Paper',
            'size_range' => SizeRangeEnum::ElevenToFifty,
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'work_mode' => WorkModeEnum::OfficeBased,
            'plan' => PlanEnum::Free,
            'is_self_hosted' => false,
        ];
    }

    /**
     * Indicate that the company runs a self hosted instance.
     */
    public function selfHosted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_self_hosted' => true,
        ]);
    }

    /**
     * Indicate that the company is still within its trial period.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_ends_at' => now()->addDays(30),
        ]);
    }
}
