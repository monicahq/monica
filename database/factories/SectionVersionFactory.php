<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VersionStatusEnum;
use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<SectionVersion> */
class SectionVersionFactory extends Factory
{
    protected $model = SectionVersion::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'account_id' => fn (array $attributes): int => Section::query()->findOrFail($attributes['section_id'])->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'resolved_value_lists' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'usage_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Retired,
            'published_at' => now(),
            'retired_at' => now(),
        ]);
    }

    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => $times,
        ]);
    }
}
