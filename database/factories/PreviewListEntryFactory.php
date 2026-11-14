<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PreviewListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PreviewListEntry> */
class PreviewListEntryFactory extends Factory
{
    protected $model = PreviewListEntry::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'locale' => 'en',
            'token' => null,
            'token_expires_at' => null,
            'admitted_at' => null,
            'registered_at' => null,
        ];
    }

    public function admitted(string $token): static
    {
        return $this->state(fn (array $attributes): array => [
            'token' => hash('sha256', $token),
            'token_expires_at' => now()->addDays(14),
            'admitted_at' => now(),
        ]);
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'admitted_at' => now()->subDay(),
            'registered_at' => now(),
        ]);
    }
}
