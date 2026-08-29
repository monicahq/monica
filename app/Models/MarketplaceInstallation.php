<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplaceUpdateDecisionEnum;
use Carbon\CarbonImmutable;
use Database\Factories\MarketplaceInstallationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class MarketplaceInstallation
 *
 * The link between something somebody installed and where it came from.
 *
 * Installing is copying. The installer gets their own private copy, which they
 * can change freely without affecting the original and without depending on its
 * author. What the copy keeps is a trace of its origin, and this row is that
 * trace. Without it there is no update notification; with it, and only with it,
 * the promise that nothing changes under somebody's feet is kept.
 *
 * Uninstalling removes the trace and leaves the copy in place. Nothing anybody
 * made is taken away.
 *
 * @property int $id
 * @property int $account_id
 * @property int $marketplace_listing_id
 * @property int $marketplace_listing_version_id
 * @property string $installable_type
 * @property int $installable_id
 * @property CarbonImmutable $installed_at
 * @property bool $has_local_changes
 * @property int|null $update_available_version_id
 * @property CarbonImmutable|null $update_notified_at
 * @property MarketplaceUpdateDecisionEnum|null $update_decision
 * @property CarbonImmutable|null $update_decided_at
 * @property array<string, mixed>|null $value_list_mapping
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read MarketplaceListing $listing
 * @property-read MarketplaceListingVersion $version
 * @property-read MarketplaceListingVersion|null $updateAvailableVersion
 */
#[Fillable([
    'account_id',
    'marketplace_listing_id',
    'marketplace_listing_version_id',
    'installable_type',
    'installable_id',
    'installed_at',
    'has_local_changes',
    'update_available_version_id',
    'update_notified_at',
    'update_decision',
    'update_decided_at',
    'value_list_mapping',
])]
class MarketplaceInstallation extends Model
{
    /** @use HasFactory<MarketplaceInstallationFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'has_local_changes' => 'boolean',
            'update_notified_at' => 'datetime',
            'update_decision' => MarketplaceUpdateDecisionEnum::class,
            'update_decided_at' => 'datetime',
            'value_list_mapping' => 'array',
        ];
    }

    /**
     * Get the account that installed it.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the listing the copy came from.
     *
     * @return BelongsTo<MarketplaceListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }

    /**
     * Get the exact version that was copied, which is what says whether the
     * copy is current.
     *
     * @return BelongsTo<MarketplaceListingVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListingVersion::class, 'marketplace_listing_version_id');
    }

    /**
     * Get the newer version the installer was told about, which is null while
     * the copy is current or while they have already decided.
     *
     * @return BelongsTo<MarketplaceListingVersion, $this>
     */
    public function updateAvailableVersion(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListingVersion::class, 'update_available_version_id');
    }

    /**
     * Get the local copy, which is a template or a section in the account of
     * the installer, fully theirs to change.
     *
     * @return MorphTo<Model, $this>
     */
    public function installable(): MorphTo
    {
        return $this->morphTo();
    }
}
