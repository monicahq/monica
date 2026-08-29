<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarketplaceUpdateDecisionEnum;
use App\Models\Account;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListingVersion;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<MarketplaceInstallation>
 */
class MarketplaceInstallationFactory extends Factory
{
    protected $model = MarketplaceInstallation::class;

    /**
     * The account installing is not the one that published, which is the whole
     * point, so the local copy is made beside it rather than taken from the
     * listing.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'marketplace_listing_version_id' => MarketplaceListingVersion::factory()->approved(),
            'marketplace_listing_id' => fn (array $attributes): int => MarketplaceListingVersion::query()->findOrFail($attributes['marketplace_listing_version_id'])->marketplace_listing_id,
            'installable_type' => Template::class,
            'installable_id' => fn (array $attributes): int => Template::factory()->create(['account_id' => $attributes['account_id']])->id,
            'installed_at' => now(),
            'has_local_changes' => false,
            'update_available_version_id' => null,
            'update_notified_at' => null,
            'update_decision' => null,
            'update_decided_at' => null,
            'value_list_mapping' => null,
        ];
    }

    /**
     * Indicate that the installer has edited their copy since, which is what
     * makes an update riskier and is shown before they apply one.
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_local_changes' => true,
        ]);
    }

    /**
     * Indicate that a newer version has been approved and the installer has
     * been told, with the decision still theirs to take.
     */
    public function withAnUpdateWaiting(?MarketplaceListingVersion $version = null): static
    {
        return $this->state([
            'update_available_version_id' => fn (array $attributes): int => ($version ?? MarketplaceListingVersion::factory()->approved()->create([
                'marketplace_listing_id' => $attributes['marketplace_listing_id'],
                'version_number' => 2,
            ]))->id,
            'update_notified_at' => now(),
            'update_decision' => MarketplaceUpdateDecisionEnum::Pending,
        ]);
    }

    /**
     * Indicate that the installer refused an update. Refusing is a real answer
     * and not a postponement, and it does not stop them being told about the
     * version after that.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'update_decision' => MarketplaceUpdateDecisionEnum::Declined,
            'update_decided_at' => now(),
        ]);
    }
}
