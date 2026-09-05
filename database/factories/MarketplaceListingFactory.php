<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarketplaceListingStatusEnum;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<MarketplaceListing> */
class MarketplaceListingFactory extends Factory
{
    protected $model = MarketplaceListing::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'listable_type' => Template::class,
            'listable_id' => Template::factory(),
            'account_id' => fn (array $attributes): int => Template::query()->findOrFail($attributes['listable_id'])->account_id,
            'marketplace_category_id' => MarketplaceCategory::factory(),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'name' => 'Medical follow up',
            'summary' => 'Everything worth writing down after an appointment',
            'description' => fake()->paragraph(),
            'locale' => 'en',
            'author_name' => 'Dwight Schrute',
            'status' => MarketplaceListingStatusEnum::Draft,
            'published_version_id' => null,
            'rejection_reason' => null,
            'moderated_at' => null,
            'moderated_by' => null,
            'install_count' => 0,
            'first_published_at' => null,
        ];
    }

    public function forSection(?Section $section = null): static
    {
        $section ??= Section::factory()->create();

        return $this->state(fn (array $attributes): array => [
            'listable_type' => Section::class,
            'listable_id' => $section->id,
            'account_id' => $section->account_id,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Submitted,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Published,
            'moderated_at' => now(),
            'moderated_by' => 'Michael Scott',
            'first_published_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'The description says nothing about what the fields are for.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Rejected,
            'rejection_reason' => $reason,
            'moderated_at' => now(),
            'moderated_by' => 'Michael Scott',
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Withdrawn,
            'published_version_id' => null,
        ]);
    }

    public function installed(int $times = 12): static
    {
        return $this->state(fn (array $attributes): array => [
            'install_count' => $times,
        ]);
    }
}
