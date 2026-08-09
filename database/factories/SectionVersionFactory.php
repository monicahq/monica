<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VersionStatusEnum;
use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionVersion>
 */
class SectionVersionFactory extends Factory
{
    protected $model = SectionVersion::class;

    /**
     * A version detached from the account of its section describes nothing, so
     * the account is taken from the section rather than made beside it.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Indicate that the version has been frozen. Nothing may be added to it,
     * changed in it or taken out of it from then on.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the version is no longer offered when somebody assembles a
     * template, while every template already containing it is untouched.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Retired,
            'published_at' => now(),
            'retired_at' => now(),
        ]);
    }

    /**
     * Indicate that template versions contain the section, which is what stops
     * the section being deleted.
     */
    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => $times,
        ]);
    }
}
