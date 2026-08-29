<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Helpers\MarketplacePayload;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<MarketplaceListingVersion>
 */
class MarketplaceListingVersionFactory extends Factory
{
    protected $model = MarketplaceListingVersion::class;

    /**
     * The payload here is a shape rather than a real structure, since most
     * tests care about where the version stands and not about what travels in
     * it. A test that does care builds its own with
     * {@see MarketplacePayload::of()}.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        $payload = ['type' => 'section', 'section' => [], 'fields' => [], 'value_lists' => []];

        return [
            'marketplace_listing_id' => MarketplaceListing::factory(),
            'version_number' => 1,
            'payload' => $payload,
            'change_note' => null,
            'status' => MarketplaceVersionStatusEnum::Submitted,
            'rejection_reason' => null,
            'approved_at' => null,
            'withdrawn_at' => null,
            'previous_version_id' => null,
            'install_count' => 0,
            'checksum' => MarketplacePayload::checksum($payload),
        ];
    }

    /**
     * Indicate that the version passed moderation and can be installed.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceVersionStatusEnum::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the version was refused, with the reason the author is
     * owed.
     */
    public function rejected(string $reason = 'The change note does not say what changed.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceVersionStatusEnum::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the version was pulled, which stops new installs and
     * changes nothing for the copies already made of it.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MarketplaceVersionStatusEnum::Withdrawn,
            'approved_at' => now(),
            'withdrawn_at' => now(),
        ]);
    }

    /**
     * Indicate that the version carries this payload, and the checksum that
     * proves it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function carrying(array $payload): static
    {
        return $this->state(fn (array $attributes): array => [
            'payload' => $payload,
            'checksum' => MarketplacePayload::checksum($payload),
        ]);
    }
}
