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

/**
 * @extends Factory<MarketplaceListing>
 */
class MarketplaceListingFactory extends Factory
{
    protected $model = MarketplaceListing::class;

    /**
     * A listing detached from the account of what it publishes describes
     * nothing, so the account is taken from the listable rather than made
     * beside it.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Indicate that what is published is a section on its own, with no template
     * or page around it.
     */
    public function forSection(?Section $section = null): static
    {
        $section ??= Section::factory()->create();

        return $this->state(fn (array $attributes): array => [
            'listable_type' => Section::class,
            'listable_id' => $section->id,
            'account_id' => $section->account_id,
        ]);
    }

    /**
     * Indicate that the author has sent it in and is waiting on a decision.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Submitted,
        ]);
    }

    /**
     * Indicate that the listing is in the catalogue and can be installed.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Published,
            'moderated_at' => now(),
            'moderated_by' => 'Michael Scott',
            'first_published_at' => now(),
        ]);
    }

    /**
     * Indicate that it was refused, with the reason the author is owed.
     */
    public function rejected(string $reason = 'The description says nothing about what the fields are for.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Rejected,
            'rejection_reason' => $reason,
            'moderated_at' => now(),
            'moderated_by' => 'Michael Scott',
        ]);
    }

    /**
     * Indicate that the author pulled it from the catalogue, which changes
     * nothing for anybody who already installed it.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceListingStatusEnum::Withdrawn,
            'published_version_id' => null,
        ]);
    }

    /**
     * Indicate that accounts have installed it, which is the quality signal the
     * catalogue shows publicly.
     */
    public function installed(int $times = 12): static
    {
        return $this->state(fn (array $attributes): array => [
            'install_count' => $times,
        ]);
    }
}
