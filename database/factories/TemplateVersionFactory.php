<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateVersion>
 */
class TemplateVersionFactory extends Factory
{
    protected $model = TemplateVersion::class;

    /**
     * A version detached from the account of its template describes nothing, so
     * the account is taken from the template rather than made beside it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'account_id' => fn (array $attributes): int => Template::query()->findOrFail($attributes['template_id'])->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'record_count' => 0,
        ];
    }

    /**
     * Indicate that the version has been frozen, which is what every rule about
     * refusing a write leans on.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the version is no longer offered for a new record, while
     * every record already on it stays exactly as it is.
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
     * Indicate that records point at the version, which is what makes the cost
     * of retiring it visible and what stops the template being deleted.
     */
    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'record_count' => $times,
        ]);
    }
}
